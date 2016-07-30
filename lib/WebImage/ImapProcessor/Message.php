<?php

namespace WebImage\ImapProcessor;

class Message {
	
	const FLAG_SEEN = 'Seen';
	const STATUS_NORMAL = 'Normal';
	const STATUS_MOVED = 'Moved';
	const STATUS_DELETED = 'Deleted';
	
	private $subject;
	private $from;
	private $to;
	private $date;
	private $size;
	private $uid;
	private $isSeen;
	private $isRecent;
	private $isFlagged;
	private $isAnswered;
	private $isDeleted;
	private $isDraft;
	private $type;
	
	private $status;
	#private $meta = array();
	
	public function __construct(Mbox $mbox, Person $from, Person $to, $subject, $date, $size, $uid, $is_seen, $is_recent, $is_flagged, $is_answered, $is_deleted, $is_draft, $type) {
		$this->status = self::STATUS_NORMAL;
		$this->mbox = $mbox;
		$this->from = $from;
		$this->to = $to;
		$this->subject = $subject;
		$this->date = $date;
		$this->size = $size;
		$this->uid = $uid;
		$this->isSeen = $is_seen;
		$this->isRecent = $is_recent;
		$this->isFlagged = $is_flagged;
		$this->isAnswered = $is_answered;
		$this->isDeleted = $is_deleted;
		$this->isDraft = $is_draft;
		$this->type = $type;
	}
	
	public function getMbox() { return $this->mbox; }
	public function getFrom() { return $this->from; }
	public function getTo() { return $this->to; }
	public function getSubject() { return $this->subject; }
	public function getDate() { return $this->date; }
	public function getSize() { return $this->size; }
	public function getUid() { return $this->uid; }
	
	public function seen($true_false=null) {
		if (null === $true_false) { // Getter
			return $this->isSeen;
		} else if (is_bool($true_false)) {
			$this->isSeen = $true_false;
			if ($true_false) {
				$this->getMbox()->setMessageFlag($this->getUid(), self::FLAG_SEEN);
			} else {
				$this->getMbox()->clearMessageFlag($this->getUid(), self::FLAG_SEEN);
			}
		} else {
			throw new \Exception(sprintf('%s was expecting a boolean value', __METHOD__));
		}
	}
	
	public function recent() { return $this->isRecent; }
	public function flagged() { return $this->isFlagged; }
	public function answered() { return $this->isAnswered; }
	public function deleted() { return $this->isDeleted; }
	public function isDraft() { return $this->isDraft; }
	public function getType() { return $this->type; }
	
	public function move($folder) {
		$this->setStatus(self::STATUS_MOVED);
		return $this->getMbox()->moveMessage($this,  $folder);
	}
	public function delete() {
		$this->setStatus(self::STATUS_DELETED);
		return $this->getMbox()->deleteMessage($this);
	}
	
	/*public function getMeta($key, $default=null) {
		if (isset($this->meta[$key])) return $this->meta[$key];
		else return $default;
	}*/
	public function getStatus() { return $this->status; }
	protected function validStatuses() { return array(self::STATUS_NORMAL, self::STATUS_DELETED, self::STATUS_MOVED); }
	protected function setStatus($status) {
		if (in_array($status, $this->validStatuses())) {
			$this->status = $status;
		}
	}
}