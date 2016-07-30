<?php

namespace WebImage\ImapProcessor;

class Mbox {
	private $mp; // mailbox resource pointer
	private $server;
	private $rootMailbox;
	private $username;
	private $password;
	private $logger;
	private $isBatchMode = false;
	private $batchActions = array();
	
	function __construct($server, $mailbox, $username, $password, Logger $logger=null)
	{
		$this->server = $server;
		$this->rootMailbox = $mailbox;
		$this->username = $username;
		$this->password = $password;
		$this->logger = $logger;
		$this->log(sprintf('Open connection: Server: %s; mailbox: %s; user: %s', $server, $mailbox, $username));
		$this->resetBatchActions();
	}
	public function __wakeup()
	{
		$this->mp = null;
	}
	public function log($message)
	{
		if (null !== $this->logger) $this->logger->log($this->getConnectionString() . ': ' . $message);
	}
	
	public function getFolders($pattern='*')
	{
		$folders = imap_getmailboxes($this->getStream(), $this->getConnectionString(), $pattern);
		$return = array();
		if (is_array($folders)) {
			foreach($folders as $folder) {
				/**
				 * $folder->name Fullly qualified folder name
				 * $folder->attributes
				 * $folder->delimiter A delimiter used to define folder hierarchy
				 **/
				$name = imap_utf7_decode($folder->name);
				$name = str_replace($this->getServerString(), '', $name);
				$return[] = $name;
			}
		}
		
		return $return;
	}
	
	public function getNumRecent() { return $this->getInfo()->Recent; }
	public function getNumMessages() { return $this->getInfo()->Nmsgs; }
	
	public function getMessages($start=1, $end=null, $fetch_options=0) {
		$messages = array();
		$n_msgs = $this->getNumMessages();

		if (null === $end) {
			$end = $n_msgs;
		}
		
		if ($n_msgs > 0)
		{
			$msg_range = sprintf('%s:%s', $start, $end);
			
			$headers = imap_fetch_overview($this->getStream(), $msg_range, $fetch_options);
			$n_retrieved = count($headers);
			
			$this->log(sprintf('Matched messages: %d', $n_retrieved));
			
			for($i=$n_retrieved-1; $i >= 0; $i--)
			{
				$header = $headers[$i];
				//mb_internal_encoding('UTF-8');
				
				$message = new Message(
					$this,
					Person::createFromString(isset($header->from) ? $header->from : ''), 
					Person::createFromString(isset($header->to) ? $header->to : ''),
					isset($header->subject) ? mb_decode_mimeheader($header->subject) : '', 
					strtotime($header->date),
					$header->size, 
					$header->uid, 
					($header->seen == 1), 
					($header->recent == 1), 
					($header->flagged == 1), 
					($header->answered == 1), 
					($header->deleted == 1),
					($header->draft == 1),
					-1 //$struct->type
				);
				
				/**
				 * if imap_fetch_overview is called with a sequence starting
				 *  with a UID higher than the higest available UID then it seems 
				 * to adjust the UID.  This ensures that messages outside of the 
				 * requested sequence range are returned.
				 **/
				if ($fetch_options & FT_UID && $message->getUid() < $start) continue;
				
				$messages[] = $message;
			}
		}
		return $messages;
	}
	/*
	public function runRules(array $rules)
	{
		$this->startBatching();
		
		$messages = $this->getMessages();
		$n_messages = count($messages);
		
		if ($n_messages == 0)
		{
			$this->log('No messages');
			return;
		}
		
		$this->log(sprintf('Running rules: %s', implode(', ', array_keys($rules))));
		
		foreach($messages as $message)
		{
			foreach($rules as $rule_name => $handler) 
			{
				$ev = new MessageEvent($rule_name, $message);
				call_user_func($handler, $ev);
				$break = $ev->isCancelled();
				unset($ev);
				if ($break) break;
			}	
		}
		
		$this->stopBatching();	
		
		return $this;
		
	}
	*/
	public function commitChanges()
	{
		imap_expunge($this->getStream());
	}
	private function processBatchActions()
	{
		// Batch move messages
		foreach($this->batchActions['move'] as $folder => $uids) {
			$this->moveMessages($uids, $folder);
		}
		
		// Batch delete messags
		$this->deleteMessages($this->batchActions['delete']);
		$this->resetBatchActions();
	}
	private function resetBatchActions() {
		$this->batchActions = array(
			'move' => array(),
			'delete' => array()
		);
	}
	
	/**
	 * @return {
	 *	messages
	 *	recent
	 * 	unseen
	 *	uidnext
	 *	uidvalidity
	 * }
	 **/
	public function getFolderInfo($folder)
	{
		return imap_status($this->getStream(), $folder, SA_ALL);
	}
	/**
	 * @return {
	 *	Date
	 *	Driver
	 * 	Mailbox
	 *	Nmsgs
	 *	Recent
	 * }
	 */
	public function getInfo()
	{
		return imap_check($this->getStream());
	}
	
	public function open($folder)
	{
		$mbox = new Mbox($this->server, $folder, $this->username, $this->password, $this->logger);
		return $mbox;
	}
	
	private function getMailbox($name)
	{
		$name = imap_utf7_encode($name);
		$name = $this->getConnectionString($name);
		return $name;
	}
	
	public function folderExists($name)
	{
		$name = $this->getMailbox($name);
		if (imap_status($this->getStream(), $name, SA_UNSEEN)) return true;
		else return false;
	}
	
	public function createFolder($name)
	{	
		if ($this->folderExists($name)) 
			return false;
		else {
			$name = $this->getMailbox($name);
			if (@imap_createmailbox($this->getStream(), $name)) return true;
			return false;
		}
	}
	
	public function deleteFolder($name) 
	{
		if ($this->folderExists($name)) 
		{
			$name = $this->getMailbox($name);
			if (@imap_deletemailbox($this->getStream(), $name)) return true;
			else return false;
		}
		else 
			echo 'Folder does  not exist' . PHP_EOL;return false;
	}
	
	public function moveMessage(Message $message, $folder) 
	{
		if ($this->isBatchMode) 
		{
			if (!isset($this->batchActions['move'][$folder])) $this->batchActions['move'][$folder] = array();
			$this->batchActions['move'][$folder][] = $message->getUid();
		}
		else
		{
			$this->log(sprintf('Moving %s to %s', $message->getUid(), $folder));
			$result = imap_mail_move($this->getStream(), $message->getUid(), $folder, CP_UID);
		}
	}
	
	private function moveMessages(array $uids, $folder) 
	{
		if (count($uids) == 0) return;
		$uids_str = implode(',', $uids);
		$this->log(sprintf('Moving %s to %s', $uids_str, $folder));
		$result = imap_mail_move($this->getStream(), $uids_str, $folder, CP_UID);
	}
	
	public function deleteMessage(Message $message) 
	{
		if ($this->isBatchMode)
		{
			$this->batchActions['delete'][] = $message->getUid();
		}
		else
		{
			$this->log(sprintf('Deleting %s', $message->getUid()));
			$result = imap_delete($this->getStream(), $message->getUid(), FT_UID);
		}
	}
	private function deleteMessages(array $uids) {
		if (count($uids) == 0) return;
		$uids_str = implode(',', $uids);
		$this->log(sprintf('Deleting %s', $uids_str));
		$result = imap_delete($this->getStream(), $uids_str, FT_UID);
	}
	public function setMessageFlag($uid, $flag) 
	{
		imap_setflag_full($this->getStream(), $uid, '\\' . $flag, ST_UID);
	}
	
	public function clearMessageFlag($uid, $flag) 
	{
		imap_clearflag_full($this->getStream(), $uid, '\\' . $flag, ST_UID);
	}
	
	public function startBatching()
	{
		$this->isBatchMode = true;
	}
	
	public function stopBatching() {
		$this->isBatching = false;
		$this->processBatchActions();
		// Finalize changes
		$this->commitChanges();
	}
	public function getHeaders() 
	{
		$cache_key = preg_replace('/[^a-z0-9]/i', '', $this->rootMailbox);
		$cache_file = sprintf('%s.cache', $cache_key);
		
		if (file_exists($cache_file)) 
		{
			$headers = unserialize(file_get_contents($cache_file));
		}
		else
		{
			$headers = imap_fetch_overview($this->getStream(), '1:'.$this->getNumMessages());
			file_put_contents($cache_file, serialize($headers));
		}
		
		return $headers;
	}
	
	public function getServerString()
	{
		return sprintf('{%s:993/imap/ssl}', $this->server);
	}
	
	public function getConnectionString($mailbox=null)
	{
		if (null === $mailbox) $mailbox = $this->rootMailbox;
		$connect_str = $this->getServerString() . $mailbox;
		return $connect_str;
	}
	
	public function getStream()
	{
		if (null === $this->mp || (is_resource($this->mp) && false === imap_ping($this->mp)))
		{
			$connect_str = $this->getConnectionString();
			$this->mp = imap_open($connect_str, $this->username, $this->password);
		}
		return $this->mp;
	}
	
	public function close()
	{
		if ($this->mp && false !== imap_ping($this->mp))
		{
			# CAUSES ERROR: $this->log(sprintf('Close connection: Server: %s; mailbox: %s; user: %s', $this->server, $this->rootMailbox, $this->username));
			imap_close($this->mp);
		}
	}
	
	function __destruct()
	{
		$this->close();
	}
}