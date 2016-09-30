<?php

namespace MP\Framework;

/**
 * Coupon code generator
 * Inspired by http://stackoverflow.com/questions/11708559/coupon-code-generation
 */
class CouponCode
{
    private static $BITCOUNT = 30; // Number of bits in code
    public static $BITMASK   = 32767; // (1 << ($BITCOUNT >> 1)) - 1
    public static $ALPHABET  = "XUF57ESCYK620D8QZNL3RHMG4W9PJTVA"; // Random shuffled alphabet
    public static $MAX       = 4294967296; // 2 << ($BITCOUNT + 1);

    /**
     * TODO
     */
    public static function init()
    {
        //self::$BITMASK = (1 << self::$BITCOUNT/2) - 1;
    }

    public static function hashFunction($num)
    {
        return ((($num ^ 47894) + 25) << 1) & self::$BITMASK;
    }

    /**
     * Hash function to scramble the bits of $num before converting to a coupon code
     */
    public static function hash($num)
    {
        $left = $num >> (self::$BITCOUNT/2);
        $right = $num & self::$BITMASK;

        for ($round = 0; $round < 9; ++$round) {
            $left = $left ^ self::hashFunction($right); // Left XOR with round func
                   $temp = $left;
            $left = $right;
            $right = $temp; // Swap
        }
        return $left | ($right << (self::$BITCOUNT/2));
    }

    /**
     * Gets a string code from a coupon ID
     * @arg $number integer coupon ID
     */ 
    public static function getCode($number)
    {
        $b = "";
        for ($i=0; $i<6; ++$i) {
            $b .= self::$ALPHABET[$number & ((1 << 5)-1)];
            $number = $number >> 5;
        }
        return $b;
    }

    /**
     * Generate an integer code from a string coupon
     * @arg $coupon string 
     */
    public static function codeFromCoupon($coupon)
    {
        str_replace('O', '0', $coupon);
        $n = 0;
        for ($i = 0; $i < 6; ++$i) {
            $n = $n | strpos(self::$ALPHABET, $coupon[$i]) << (5 * $i);
        }
        return $n;
    }

    /**
     * Sshuffle items using a seed 
     */
    public static function seeded_shuffle(&$items, $seed = false)
    {
        $items = array_values($items);
        mt_srand($seed ? $seed : time());
        for ($i = count($items) - 1; $i > 0; $i--) {
            $j = mt_rand(0, $i);
            list($items[$i], $items[$j]) = array($items[$j], $items[$i]);
        }
    }

    /**
     * Unshuffle items using a seed 
     */
    public static function seeded_unshuffle(&$items, $seed)
    {
        $items = array_values($items);

        mt_srand($seed);
        $indices = array();
        for ($i = count($items) - 1; $i > 0; $i--) {
            $indices[$i] = mt_rand(0, $i);
        }

        foreach (array_reverse($indices, true) as $i => $j) {
            list($items[$i], $items[$j]) = array($items[$j], $items[$i]);
        }
    }

    /**
     * Shuffle the alphabet using a seed. This is useful if you want previous codes
     * to change at regular intervals. Just rotate your seed.
     */
    public static function shuffleAlphabet($seed)
    {
        $chars = str_split(self::$ALPHABET);
        self::seeded_shuffle($chars, $seed ^ 59721);
        self::$ALPHABET = join('', $chars);
    }
}

// CLI tests / sample usage
if (isset($argv) && $argc > 1) {
    CouponCode::init();
    CouponCode::shuffleAlphabet(1);
    CouponCode::$MAX = 10;
    printf("%10s\t%10s\t%s\t%s\n", "Input num", "Hashed", "Code", "Recovered num");
    printf("------------------------------------------------------\n");

    //for($i=0;$i<PHP_INT_MAX;$i++) { /* full test */
    for ($i=CouponCode::$MAX;$i>=0;$i--) {
        $c = CouponCode::hash($i);
        $recovered = CouponCode::hash(CouponCode::codeFromCoupon(CouponCode::getCode($c)));
        assert ( $recovered == $i);
        
        printf("%10d\t%10d\t%d\t%s\t%s\n",
            $i, 
            $c,
            CouponCode::hash($c),
            CouponCode::getCode($c),
            $recovered
        );
    }
}
