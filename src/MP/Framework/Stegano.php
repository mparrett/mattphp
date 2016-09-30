<?php

namespace MP\Framework;

/**
 * MattPHP
 * Fun steganographic utilities
 * @author Matt Parrett
 */
class Stegano
{

    /**
     * Simple 16 bit hash function (132 + k + (32 * id))
     */
    public static function simpleHash($id, $k)
    {
        return (132 + $k + (($id << 5) % 65536) + $id) % 65536;
    }

    /**
      * Generate a scrambled version of the input ID 
      * for a single Feistel Cipher round
      *
      * MySQL max for pid is 4294967295 (32 bits)
      */
    private static function scrambleID($id, $rnd, $reverse = false)
    {
        $lo16mask = 0xffff;
        $hi16mask = 0xffff0000;

        $lo = $id & $lo16mask;
        $hi = $id & $hi16mask;

        //echo '<pre>'; var_dump(dechex($id)); var_dump(dechex($hi)); var_dump(dechex($lo));

        if ($reverse) {
            $hihash = self::simpleHash($hi >> 16, $rnd);
            $lo ^= $hihash;
            $ret = (($hi >> 16) & $lo16mask) | (($lo << 16) & $hi16mask);
        } else {
            $lohash = self::simpleHash($lo, $rnd);
            $hi ^= $lohash << 16;
            $ret = (($hi >> 16) & $lo16mask) | (($lo << 16) & $hi16mask);
        }

        return $ret;
    }

    /**
     * Scramble an ID using a Feistel cipher
     * @arg  $kvals  array of values to use for the rounds of ciphering
     */
    public static function scramble($id, array $kvals)
    {
        $out = $id;
        for ($i = 0, $len = count($kvals); $i < $len; $i++) {
            $out = Stegano::scrambleID($out, $kvals[$i]);
        }
        return $out;
    }

    /**
     * Unscramble an ID using a Feistel cipher
     * @arg  $kvals  array of values to use for the rounds of deciphering
     * Must match values used to encrypt.
     */
    public static function unscramble($id, array $kvals)
    {
        $out = $id;
        for ($i = count($kvals) - 1; $i >= 0; $i--) {
            $out = Stegano::scrambleID($out, $kvals[$i], true);
        }
        return $out;
    }

    /**
     * Hides an integer in a MD5-like string
     * @arg  $id  32 bit integer
     */
    public static function disguiseLikeMD5($id)
    {
        $binpid = str_pad(decbin($id), 32, "0", STR_PAD_LEFT);

        $str = '';
        $hexchars_on = '13579ace';
        $hexchars_off = '24680bdf';

        for ($i = 0; $i < 32; $i++) {
            if ($binpid[$i]) { // set bit
                $str .= $hexchars_on[ mt_rand(0, 7)];
            } else { // unset bit
                $str .= $hexchars_off[ mt_rand(0, 7)];
            }
        }
        return $str;
    }

   /**
    * Recovers a 32 bit integer from a faux MD5 hash
    * @arg  $md5  md5 hash string
    */
    public static function recoverFromMD5($md5)
    {
        for ($i = 0; $i < 32; $i++) {
            $md5[$i] = ord($md5[$i]) & 0x01;
        }
        return bindec($md5);
    }
}

// CLI tests / sample usage
if (isset($argv) && $argc > 1) {
    $rounds = [mt_rand(0, 16384), mt_rand(0, 16384), mt_rand(0, 16384), mt_rand(0, 16384)];
    $rnd_id = mt_rand();

    //$kvals = [1,2,3,4];
    echo "ID:\t\t $rnd_id\n";
    echo "Rounds:\t\t " . join(",", $rounds) . "\n";
    
    $scrambled = Stegano::scramble($rnd_id, $rounds);
    echo "Scrambled:\t $scrambled\n";
    $hidden = Stegano::disguiseLikeMD5($scrambled);
    echo "Hidden:\t\t $hidden\n";
    $recovered = Stegano::recoverFromMD5($hidden);
    echo "Recovered:\t $recovered\n";
    echo "Unscrambled:\t " . Stegano::unscramble($recovered, $rounds) . "\n";
    assert($scrambled == $recovered);
    echo "Done!\n";
}
