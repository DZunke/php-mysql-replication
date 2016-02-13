<?php

/**
 * Class Gtid
 */
class Gtid
{
    /**
     * @var string
     */
    private $gtid = '';
    /**
     * @var array
     */
    private $intervals = [];
    /**
     * @var mixed|string
     */
    private $sid = '';

    /**
     * Gtid constructor.
     * @param $gtid
     */
    public function __construct($gtid)
    {
        $this->gtid = $gtid;

        preg_match('/^([0-9a-fA-F]{8}(?:-[0-9a-fA-F]{4}){3}-[0-9a-fA-F]{12})((?::[0-9-]+)+)$/', $this->gtid, $matches);

        $this->sid = $matches[1];
        foreach (array_filter(explode(':', $matches[2])) as $k)
        {
            $this->intervals[] = explode('-', $k);
        }
        $this->sid = str_replace('-', '', $this->sid);
    }

    /**
     * @return string
     */
    public function encode()
    {
        $buffer = pack('H*', $this->sid);
        $buffer .= pack('Q', count($this->intervals));

        foreach ($this->intervals as $interval)
        {
            if (count($interval) != 1)
            {
                $buffer .= pack('Q', $interval[0]);
                $buffer .= pack('Q', $interval[1]);
            }
            else
            {
                $buffer .= pack('Q', $interval[0]);
                $buffer .= pack('Q', $interval[0] + 1);
            }
        }

        return $buffer;
    }

    /**
     * @return int
     */
    public function encoded_length()
    {
        return (16 + 8 + 2 * 8 * count($this->intervals));
    }
}


/**
 * Class GtidSet
 */
class GtidSet
{
    /**
     * @var Gtid []
     */
    private $gtids;

    /**
     * GtidSet constructor.
     * @param $gtids
     */
    public function __construct($gtids)
    {
        foreach (explode(',', $gtids) as $gtid)
        {
            $this->gtids[] = new Gtid($gtid);
        }
    }

    /**
     * @return int
     */
    public function encoded_length()
    {
        $l = 8;

        foreach ($this->gtids as $gtid)
        {
            $l += $gtid->encoded_length();
        }

        return $l;
    }

    /**
     * @return string
     */
    public function encoded()
    {
        $s = pack('Q', count($this->gtids));

        foreach ($this->gtids as $gtid)
        {
            $s .= $gtid->encode();
        }

        return $s;
    }
}