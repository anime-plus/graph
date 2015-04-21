<?php
class Queue
{
	private $file = null;
	private $handle = null;

	public function __construct($file)
	{
		$this->file = $file;
	}

	public function seek(QueueItem $item)
	{
		$this->open();
		$items = $this->readItems();
		$this->close();
		foreach ($items as $index => $otherItem)
		{
			if ($otherItem->item == $item->item)
				return $index + 1;
		}
		return false;
	}

	public function peek()
	{
		$this->open();
		$items = $this->readItems();
		if (count($items) > 0)
			$item = array_shift($items);
		else
			$item = null;
		$this->close();
		return $item;
	}

	public function dequeue()
	{
		$this->open();
		$items = $this->readItems();
		if (count($items) > 0)
			$item = array_shift($items);
		else
			$item = null;
		$this->writeItems($items);
		$this->close();
		return $item;
	}

	public function enqueue(QueueItem $newItem, $enqueueAtStart = false)
	{
		$this->enqueueMultiple([$newItem], $enqueueAtStart);
	}

	public function enqueueMultiple(array $newItems, $enqueueAtStart = false)
	{
		$this->open();
		$items = $this->readItems();

		$oldItemKeys = array_flip(array_map(function($oldItem)
		{
			return $oldItem->item;
		}, $items));
		$newItems = array_filter($newItems, function($newItem) use ($oldItemKeys)
		{
			return !isset($oldItemKeys[$newItem->item]);
		});

		if ($enqueueAtStart)
		{
			array_splice($items, 0, 0, $newItems);
		}
		else
		{
			array_splice($items, count($items), 0, $newItems);
		}
		$this->writeItems($items);
		$this->close();
	}

	public function size()
	{
		$this->open();
		$items = $this->readItems();
		$this->close();
		return count($items);
	}



	private function open()
	{
		$this->handle = fopen($this->file, 'r+b');
		flock($this->handle, LOCK_EX);
	}

	private function itemFromLine($line)
	{
		if (strpos($line, "\t") === false)
		{
			$item = $line;
			$attempts = 0;
		}
		else
		{
			list ($item, $attempts) = explode("\t", $line);
			$attempts = intval($attempts);
		}
		return new QueueItem($item, $attempts);
	}

	private function lineFromItem(QueueItem $item)
	{
		return $item->item . "\t" . $item->attempts;
	}

	private function readItems()
	{
		assert($this->handle != null);
		fseek($this->handle, 0, SEEK_END);
		$size = ftell($this->handle);
		fseek($this->handle, 0, SEEK_SET);
		$data = $size > 0
			? fread($this->handle, $size)
			: null;
		$lines = explode("\n", $data);
		$lines = array_filter($lines);
		return array_map([__CLASS__, 'itemFromLine'], $lines);
	}

	private function writeItems($items)
	{
		$lines = array_map([__CLASS__, 'lineFromItem'], $items);
		$data = join("\n", $lines);
		fseek($this->handle, 0, SEEK_SET);
		ftruncate($this->handle, strlen($data));
		fwrite($this->handle, $data);
	}

	private function close()
	{
		fclose($this->handle);
		$this->handle = null;
	}

}
