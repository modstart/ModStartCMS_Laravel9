<?php

namespace Sabre\VObject;


class FreeBusyData
{
    
    protected $start;

    
    protected $end;

    
    protected $data;

    public function __construct($start, $end)
    {
        $this->start = $start;
        $this->end = $end;
        $this->data = [];

        $this->data[] = [
            'start' => $this->start,
            'end' => $this->end,
            'type' => 'FREE',
        ];
    }

    
    public function add($start, $end, $type)
    {
        if ($start > $this->end || $end < $this->start) {
                        return;
        }

        if ($start < $this->start) {
                        $start = $this->start;
        }
        if ($end > $this->end) {
                        $end = $this->end;
        }

                $currentIndex = 0;
        while ($start > $this->data[$currentIndex]['end']) {
            ++$currentIndex;
        }

                        $insertStartIndex = $currentIndex + 1;

        $newItem = [
            'start' => $start,
            'end' => $end,
            'type' => $type,
        ];

        $preceedingItem = $this->data[$insertStartIndex - 1];
        if ($this->data[$insertStartIndex - 1]['start'] === $start) {
                        --$insertStartIndex;
        }

                                        if ($insertStartIndex > 0) {
            $currentIndex = $insertStartIndex - 1;
        } else {
            $currentIndex = 0;
        }

        while ($end > $this->data[$currentIndex]['end']) {
            ++$currentIndex;
        }

                $newItems = [
            $newItem,
        ];

                        $itemsToDelete = $currentIndex - $insertStartIndex;
        if ($this->data[$currentIndex]['end'] <= $end) {
            ++$itemsToDelete;
        }

                                        if (-1 === $itemsToDelete) {
            $itemsToDelete = 0;
            if ($newItem['end'] < $preceedingItem['end']) {
                $newItems[] = [
                    'start' => $newItem['end'] + 1,
                    'end' => $preceedingItem['end'],
                    'type' => $preceedingItem['type'],
                ];
            }
        }

        array_splice(
            $this->data,
            $insertStartIndex,
            $itemsToDelete,
            $newItems
        );

        $doMerge = false;
        $mergeOffset = $insertStartIndex;
        $mergeItem = $newItem;
        $mergeDelete = 1;

        if (isset($this->data[$insertStartIndex - 1])) {
                        $this->data[$insertStartIndex - 1]['end'] = $start;

                                    if ($this->data[$insertStartIndex - 1]['type'] === $this->data[$insertStartIndex]['type']) {
                $doMerge = true;
                --$mergeOffset;
                ++$mergeDelete;
                $mergeItem['start'] = $this->data[$insertStartIndex - 1]['start'];
            }
        }
        if (isset($this->data[$insertStartIndex + 1])) {
                        $this->data[$insertStartIndex + 1]['start'] = $end;

                                    if ($this->data[$insertStartIndex + 1]['type'] === $this->data[$insertStartIndex]['type']) {
                $doMerge = true;
                ++$mergeDelete;
                $mergeItem['end'] = $this->data[$insertStartIndex + 1]['end'];
            }
        }
        if ($doMerge) {
            array_splice(
                $this->data,
                $mergeOffset,
                $mergeDelete,
                [$mergeItem]
            );
        }
    }

    public function getData()
    {
        return $this->data;
    }
}
