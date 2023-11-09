<?php
/**
 * Set the mbstring internal encoding to a binary safe encoding when func_overload
 * is enabled.
 *
 * When mbstring.func_overload is in use for multi-byte encodings, the results from
 * strlen() and similar functions respect the utf8 characters, causing binary data
 * to return incorrect lengths.
 *
 * This function overrides the mbstring encoding to a binary-safe encoding, and
 * resets it to the users expected encoding afterwards through the
 * `reset_mbstring_encoding` function.
 *
 * It is safe to recursively call this function, however each
 * `mbstring_binary_safe_encoding()` call must be followed up with an equal number
 * of `reset_mbstring_encoding()` calls.
 *
 * @since 3.7.0
 *
 * @see reset_mbstring_encoding()
 *
 * @staticvar array $encodings
 * @staticvar bool  $overloaded
 *
 * @param bool $reset Optional. Whether to reset the encoding back to a previously-set encoding.
 *                    Default false.
 */
function mbstring_binary_safe_encoding( $reset = false ) {
  static $encodings = array();
  static $overloaded = null;

  if ( is_null( $overloaded ) )
    $overloaded = function_exists( 'mb_internal_encoding' ) && ( ini_get( 'mbstring.func_overload' ) & 2 );

  if ( false === $overloaded )
    return;

  if ( ! $reset ) {
    $encoding = mb_internal_encoding();
    array_push( $encodings, $encoding );
    mb_internal_encoding( 'ISO-8859-1' );
  }

  if ( $reset && $encodings ) {
    $encoding = array_pop( $encodings );
    mb_internal_encoding( $encoding );
  }
}

/**
 * Reset the mbstring internal encoding to a users previously set encoding.
 *
 * @see mbstring_binary_safe_encoding()
 *
 * @since 3.7.0
 */
function reset_mbstring_encoding() {
  mbstring_binary_safe_encoding( true );
}

/**
 * Encode the Unicode values to be used in the URI.
 *
 * @since 1.5.0
 *
 * @param string $utf8_string
 * @param int    $length Max  length of the string
 * @return string String with Unicode encoded for URI.
 */
function utf8_uri_encode( $utf8_string, $length = 0 ) {
  $unicode = '';
  $values = array();
  $num_octets = 1;
  $unicode_length = 0;

  mbstring_binary_safe_encoding();
  $string_length = strlen( $utf8_string );
  reset_mbstring_encoding();

  for ($i = 0; $i < $string_length; $i++ ) {

    $value = ord( $utf8_string[ $i ] );

    if ( $value < 128 ) {
      if ( $length && ( $unicode_length >= $length ) )
        break;
      $unicode .= chr($value);
      $unicode_length++;
    } else {
      if ( count( $values ) == 0 ) {
        if ( $value < 224 ) {
          $num_octets = 2;
        } elseif ( $value < 240 ) {
          $num_octets = 3;
        } else {
          $num_octets = 4;
        }
      }

      $values[] = $value;

      if ( $length && ( $unicode_length + ($num_octets * 3) ) > $length )
        break;
      if ( count( $values ) == $num_octets ) {
        for ( $j = 0; $j < $num_octets; $j++ ) {
          $unicode .= '%' . dechex( $values[ $j ] );
        }

        $unicode_length += $num_octets * 3;

        $values = array();
        $num_octets = 1;
      }
    }
  }

  return $unicode;
}

/**
 * Checks to see if a string is utf8 encoded.
 *
 * NOTE: This function checks for 5-Byte sequences, UTF8
 *       has Bytes Sequences with a maximum length of 4.
 *
 * @author bmorel at ssi dot fr (modified)
 * @since 1.2.1
 *
 * @param string $str The string to be checked
 * @return bool True if $str fits a UTF-8 model, false otherwise.
 */
function seems_utf8( $str ) {
  mbstring_binary_safe_encoding();
  $length = strlen($str);
  reset_mbstring_encoding();
  for ($i=0; $i < $length; $i++) {
    $c = ord($str[$i]);
    if ($c < 0x80) $n = 0; // 0bbbbbbb
    elseif (($c & 0xE0) == 0xC0) $n=1; // 110bbbbb
    elseif (($c & 0xF0) == 0xE0) $n=2; // 1110bbbb
    elseif (($c & 0xF8) == 0xF0) $n=3; // 11110bbb
    elseif (($c & 0xFC) == 0xF8) $n=4; // 111110bb
    elseif (($c & 0xFE) == 0xFC) $n=5; // 1111110b
    else return false; // Does not match any model
    for ($j=0; $j<$n; $j++) { // n bytes matching 10bbbbbb follow ?
      if ((++$i == $length) || ((ord($str[$i]) & 0xC0) != 0x80))
        return false;
    }
  }
  return true;
}

/**
 * Converts all accent characters to ASCII characters.
 *
 * If there are no accent characters, then the string given is just returned.
 *
 * **Accent characters converted:**
 *
 * Currency signs:
 *
 * |   Code   | Glyph | Replacement |     Description     |
 * | -------- | ----- | ----------- | ------------------- |
 * | U+00A3   | £     | (empty)     | British Pound sign  |
 * | U+20AC   | €     | E           | Euro sign           |
 *
 * Decompositions for Latin-1 Supplement:
 *
 * |  Code   | Glyph | Replacement |               Description              |
 * | ------- | ----- | ----------- | -------------------------------------- |
 * | U+00AA  | ª     | a           | Feminine ordinal indicator             |
 * | U+00BA  | º     | o           | Masculine ordinal indicator            |
 * | U+00C0  | À     | A           | Latin capital letter A with grave      |
 * | U+00C1  | Á     | A           | Latin capital letter A with acute      |
 * | U+00C2  | Â     | A           | Latin capital letter A with circumflex |
 * | U+00C3  | Ã     | A           | Latin capital letter A with tilde      |
 * | U+00C4  | Ä     | A           | Latin capital letter A with diaeresis  |
 * | U+00C5  | Å     | A           | Latin capital letter A with ring above |
 * | U+00C6  | Æ     | AE          | Latin capital letter AE                |
 * | U+00C7  | Ç     | C           | Latin capital letter C with cedilla    |
 * | U+00C8  | È     | E           | Latin capital letter E with grave      |
 * | U+00C9  | É     | E           | Latin capital letter E with acute      |
 * | U+00CA  | Ê     | E           | Latin capital letter E with circumflex |
 * | U+00CB  | Ë     | E           | Latin capital letter E with diaeresis  |
 * | U+00CC  | Ì     | I           | Latin capital letter I with grave      |
 * | U+00CD  | Í     | I           | Latin capital letter I with acute      |
 * | U+00CE  | Î     | I           | Latin capital letter I with circumflex |
 * | U+00CF  | Ï     | I           | Latin capital letter I with diaeresis  |
 * | U+00D0  | Ð     | D           | Latin capital letter Eth               |
 * | U+00D1  | Ñ     | N           | Latin capital letter N with tilde      |
 * | U+00D2  | Ò     | O           | Latin capital letter O with grave      |
 * | U+00D3  | Ó     | O           | Latin capital letter O with acute      |
 * | U+00D4  | Ô     | O           | Latin capital letter O with circumflex |
 * | U+00D5  | Õ     | O           | Latin capital letter O with tilde      |
 * | U+00D6  | Ö     | O           | Latin capital letter O with diaeresis  |
 * | U+00D8  | Ø     | O           | Latin capital letter O with stroke     |
 * | U+00D9  | Ù     | U           | Latin capital letter U with grave      |
 * | U+00DA  | Ú     | U           | Latin capital letter U with acute      |
 * | U+00DB  | Û     | U           | Latin capital letter U with circumflex |
 * | U+00DC  | Ü     | U           | Latin capital letter U with diaeresis  |
 * | U+00DD  | Ý     | Y           | Latin capital letter Y with acute      |
 * | U+00DE  | Þ     | TH          | Latin capital letter Thorn             |
 * | U+00DF  | ß     | s           | Latin small letter sharp s             |
 * | U+00E0  | à     | a           | Latin small letter a with grave        |
 * | U+00E1  | á     | a           | Latin small letter a with acute        |
 * | U+00E2  | â     | a           | Latin small letter a with circumflex   |
 * | U+00E3  | ã     | a           | Latin small letter a with tilde        |
 * | U+00E4  | ä     | a           | Latin small letter a with diaeresis    |
 * | U+00E5  | å     | a           | Latin small letter a with ring above   |
 * | U+00E6  | æ     | ae          | Latin small letter ae                  |
 * | U+00E7  | ç     | c           | Latin small letter c with cedilla      |
 * | U+00E8  | è     | e           | Latin small letter e with grave        |
 * | U+00E9  | é     | e           | Latin small letter e with acute        |
 * | U+00EA  | ê     | e           | Latin small letter e with circumflex   |
 * | U+00EB  | ë     | e           | Latin small letter e with diaeresis    |
 * | U+00EC  | ì     | i           | Latin small letter i with grave        |
 * | U+00ED  | í     | i           | Latin small letter i with acute        |
 * | U+00EE  | î     | i           | Latin small letter i with circumflex   |
 * | U+00EF  | ï     | i           | Latin small letter i with diaeresis    |
 * | U+00F0  | ð     | d           | Latin small letter Eth                 |
 * | U+00F1  | ñ     | n           | Latin small letter n with tilde        |
 * | U+00F2  | ò     | o           | Latin small letter o with grave        |
 * | U+00F3  | ó     | o           | Latin small letter o with acute        |
 * | U+00F4  | ô     | o           | Latin small letter o with circumflex   |
 * | U+00F5  | õ     | o           | Latin small letter o with tilde        |
 * | U+00F6  | ö     | o           | Latin small letter o with diaeresis    |
 * | U+00F8  | ø     | o           | Latin small letter o with stroke       |
 * | U+00F9  | ù     | u           | Latin small letter u with grave        |
 * | U+00FA  | ú     | u           | Latin small letter u with acute        |
 * | U+00FB  | û     | u           | Latin small letter u with circumflex   |
 * | U+00FC  | ü     | u           | Latin small letter u with diaeresis    |
 * | U+00FD  | ý     | y           | Latin small letter y with acute        |
 * | U+00FE  | þ     | th          | Latin small letter Thorn               |
 * | U+00FF  | ÿ     | y           | Latin small letter y with diaeresis    |
 *
 * Decompositions for Latin Extended-A:
 *
 * |  Code   | Glyph | Replacement |                    Description                    |
 * | ------- | ----- | ----------- | ------------------------------------------------- |
 * | U+0100  | Ā     | A           | Latin capital letter A with macron                |
 * | U+0101  | ā     | a           | Latin small letter a with macron                  |
 * | U+0102  | Ă     | A           | Latin capital letter A with breve                 |
 * | U+0103  | ă     | a           | Latin small letter a with breve                   |
 * | U+0104  | Ą     | A           | Latin capital letter A with ogonek                |
 * | U+0105  | ą     | a           | Latin small letter a with ogonek                  |
 * | U+01006 | Ć     | C           | Latin capital letter C with acute                 |
 * | U+0107  | ć     | c           | Latin small letter c with acute                   |
 * | U+0108  | Ĉ     | C           | Latin capital letter C with circumflex            |
 * | U+0109  | ĉ     | c           | Latin small letter c with circumflex              |
 * | U+010A  | Ċ     | C           | Latin capital letter C with dot above             |
 * | U+010B  | ċ     | c           | Latin small letter c with dot above               |
 * | U+010C  | Č     | C           | Latin capital letter C with caron                 |
 * | U+010D  | č     | c           | Latin small letter c with caron                   |
 * | U+010E  | Ď     | D           | Latin capital letter D with caron                 |
 * | U+010F  | ď     | d           | Latin small letter d with caron                   |
 * | U+0110  | Đ     | D           | Latin capital letter D with stroke                |
 * | U+0111  | đ     | d           | Latin small letter d with stroke                  |
 * | U+0112  | Ē     | E           | Latin capital letter E with macron                |
 * | U+0113  | ē     | e           | Latin small letter e with macron                  |
 * | U+0114  | Ĕ     | E           | Latin capital letter E with breve                 |
 * | U+0115  | ĕ     | e           | Latin small letter e with breve                   |
 * | U+0116  | Ė     | E           | Latin capital letter E with dot above             |
 * | U+0117  | ė     | e           | Latin small letter e with dot above               |
 * | U+0118  | Ę     | E           | Latin capital letter E with ogonek                |
 * | U+0119  | ę     | e           | Latin small letter e with ogonek                  |
 * | U+011A  | Ě     | E           | Latin capital letter E with caron                 |
 * | U+011B  | ě     | e           | Latin small letter e with caron                   |
 * | U+011C  | Ĝ     | G           | Latin capital letter G with circumflex            |
 * | U+011D  | ĝ     | g           | Latin small letter g with circumflex              |
 * | U+011E  | Ğ     | G           | Latin capital letter G with breve                 |
 * | U+011F  | ğ     | g           | Latin small letter g with breve                   |
 * | U+0120  | Ġ     | G           | Latin capital letter G with dot above             |
 * | U+0121  | ġ     | g           | Latin small letter g with dot above               |
 * | U+0122  | Ģ     | G           | Latin capital letter G with cedilla               |
 * | U+0123  | ģ     | g           | Latin small letter g with cedilla                 |
 * | U+0124  | Ĥ     | H           | Latin capital letter H with circumflex            |
 * | U+0125  | ĥ     | h           | Latin small letter h with circumflex              |
 * | U+0126  | Ħ     | H           | Latin capital letter H with stroke                |
 * | U+0127  | ħ     | h           | Latin small letter h with stroke                  |
 * | U+0128  | Ĩ     | I           | Latin capital letter I with tilde                 |
 * | U+0129  | ĩ     | i           | Latin small letter i with tilde                   |
 * | U+012A  | Ī     | I           | Latin capital letter I with macron                |
 * | U+012B  | ī     | i           | Latin small letter i with macron                  |
 * | U+012C  | Ĭ     | I           | Latin capital letter I with breve                 |
 * | U+012D  | ĭ     | i           | Latin small letter i with breve                   |
 * | U+012E  | Į     | I           | Latin capital letter I with ogonek                |
 * | U+012F  | į     | i           | Latin small letter i with ogonek                  |
 * | U+0130  | İ     | I           | Latin capital letter I with dot above             |
 * | U+0131  | ı     | i           | Latin small letter dotless i                      |
 * | U+0132  | Ĳ     | IJ          | Latin capital ligature IJ                         |
 * | U+0133  | ĳ     | ij          | Latin small ligature ij                           |
 * | U+0134  | Ĵ     | J           | Latin capital letter J with circumflex            |
 * | U+0135  | ĵ     | j           | Latin small letter j with circumflex              |
 * | U+0136  | Ķ     | K           | Latin capital letter K with cedilla               |
 * | U+0137  | ķ     | k           | Latin small letter k with cedilla                 |
 * | U+0138  | ĸ     | k           | Latin small letter Kra                            |
 * | U+0139  | Ĺ     | L           | Latin capital letter L with acute                 |
 * | U+013A  | ĺ     | l           | Latin small letter l with acute                   |
 * | U+013B  | Ļ     | L           | Latin capital letter L with cedilla               |
 * | U+013C  | ļ     | l           | Latin small letter l with cedilla                 |
 * | U+013D  | Ľ     | L           | Latin capital letter L with caron                 |
 * | U+013E  | ľ     | l           | Latin small letter l with caron                   |
 * | U+013F  | Ŀ     | L           | Latin capital letter L with middle dot            |
 * | U+0140  | ŀ     | l           | Latin small letter l with middle dot              |
 * | U+0141  | Ł     | L           | Latin capital letter L with stroke                |
 * | U+0142  | ł     | l           | Latin small letter l with stroke                  |
 * | U+0143  | Ń     | N           | Latin capital letter N with acute                 |
 * | U+0144  | ń     | n           | Latin small letter N with acute                   |
 * | U+0145  | Ņ     | N           | Latin capital letter N with cedilla               |
 * | U+0146  | ņ     | n           | Latin small letter n with cedilla                 |
 * | U+0147  | Ň     | N           | Latin capital letter N with caron                 |
 * | U+0148  | ň     | n           | Latin small letter n with caron                   |
 * | U+0149  | ŉ     | n           | Latin small letter n preceded by apostrophe       |
 * | U+014A  | Ŋ     | N           | Latin capital letter Eng                          |
 * | U+014B  | ŋ     | n           | Latin small letter Eng                            |
 * | U+014C  | Ō     | O           | Latin capital letter O with macron                |
 * | U+014D  | ō     | o           | Latin small letter o with macron                  |
 * | U+014E  | Ŏ     | O           | Latin capital letter O with breve                 |
 * | U+014F  | ŏ     | o           | Latin small letter o with breve                   |
 * | U+0150  | Ő     | O           | Latin capital letter O with double acute          |
 * | U+0151  | ő     | o           | Latin small letter o with double acute            |
 * | U+0152  | Œ     | OE          | Latin capital ligature OE                         |
 * | U+0153  | œ     | oe          | Latin small ligature oe                           |
 * | U+0154  | Ŕ     | R           | Latin capital letter R with acute                 |
 * | U+0155  | ŕ     | r           | Latin small letter r with acute                   |
 * | U+0156  | Ŗ     | R           | Latin capital letter R with cedilla               |
 * | U+0157  | ŗ     | r           | Latin small letter r with cedilla                 |
 * | U+0158  | Ř     | R           | Latin capital letter R with caron                 |
 * | U+0159  | ř     | r           | Latin small letter r with caron                   |
 * | U+015A  | Ś     | S           | Latin capital letter S with acute                 |
 * | U+015B  | ś     | s           | Latin small letter s with acute                   |
 * | U+015C  | Ŝ     | S           | Latin capital letter S with circumflex            |
 * | U+015D  | ŝ     | s           | Latin small letter s with circumflex              |
 * | U+015E  | Ş     | S           | Latin capital letter S with cedilla               |
 * | U+015F  | ş     | s           | Latin small letter s with cedilla                 |
 * | U+0160  | Š     | S           | Latin capital letter S with caron                 |
 * | U+0161  | š     | s           | Latin small letter s with caron                   |
 * | U+0162  | Ţ     | T           | Latin capital letter T with cedilla               |
 * | U+0163  | ţ     | t           | Latin small letter t with cedilla                 |
 * | U+0164  | Ť     | T           | Latin capital letter T with caron                 |
 * | U+0165  | ť     | t           | Latin small letter t with caron                   |
 * | U+0166  | Ŧ     | T           | Latin capital letter T with stroke                |
 * | U+0167  | ŧ     | t           | Latin small letter t with stroke                  |
 * | U+0168  | Ũ     | U           | Latin capital letter U with tilde                 |
 * | U+0169  | ũ     | u           | Latin small letter u with tilde                   |
 * | U+016A  | Ū     | U           | Latin capital letter U with macron                |
 * | U+016B  | ū     | u           | Latin small letter u with macron                  |
 * | U+016C  | Ŭ     | U           | Latin capital letter U with breve                 |
 * | U+016D  | ŭ     | u           | Latin small letter u with breve                   |
 * | U+016E  | Ů     | U           | Latin capital letter U with ring above            |
 * | U+016F  | ů     | u           | Latin small letter u with ring above              |
 * | U+0170  | Ű     | U           | Latin capital letter U with double acute          |
 * | U+0171  | ű     | u           | Latin small letter u with double acute            |
 * | U+0172  | Ų     | U           | Latin capital letter U with ogonek                |
 * | U+0173  | ų     | u           | Latin small letter u with ogonek                  |
 * | U+0174  | Ŵ     | W           | Latin capital letter W with circumflex            |
 * | U+0175  | ŵ     | w           | Latin small letter w with circumflex              |
 * | U+0176  | Ŷ     | Y           | Latin capital letter Y with circumflex            |
 * | U+0177  | ŷ     | y           | Latin small letter y with circumflex              |
 * | U+0178  | Ÿ     | Y           | Latin capital letter Y with diaeresis             |
 * | U+0179  | Ź     | Z           | Latin capital letter Z with acute                 |
 * | U+017A  | ź     | z           | Latin small letter z with acute                   |
 * | U+017B  | Ż     | Z           | Latin capital letter Z with dot above             |
 * | U+017C  | ż     | z           | Latin small letter z with dot above               |
 * | U+017D  | Ž     | Z           | Latin capital letter Z with caron                 |
 * | U+017E  | ž     | z           | Latin small letter z with caron                   |
 * | U+017F  | ſ     | s           | Latin small letter long s                         |
 * | U+01A0  | Ơ     | O           | Latin capital letter O with horn                  |
 * | U+01A1  | ơ     | o           | Latin small letter o with horn                    |
 * | U+01AF  | Ư     | U           | Latin capital letter U with horn                  |
 * | U+01B0  | ư     | u           | Latin small letter u with horn                    |
 * | U+01CD  | Ǎ     | A           | Latin capital letter A with caron                 |
 * | U+01CE  | ǎ     | a           | Latin small letter a with caron                   |
 * | U+01CF  | Ǐ     | I           | Latin capital letter I with caron                 |
 * | U+01D0  | ǐ     | i           | Latin small letter i with caron                   |
 * | U+01D1  | Ǒ     | O           | Latin capital letter O with caron                 |
 * | U+01D2  | ǒ     | o           | Latin small letter o with caron                   |
 * | U+01D3  | Ǔ     | U           | Latin capital letter U with caron                 |
 * | U+01D4  | ǔ     | u           | Latin small letter u with caron                   |
 * | U+01D5  | Ǖ     | U           | Latin capital letter U with diaeresis and macron  |
 * | U+01D6  | ǖ     | u           | Latin small letter u with diaeresis and macron    |
 * | U+01D7  | Ǘ     | U           | Latin capital letter U with diaeresis and acute   |
 * | U+01D8  | ǘ     | u           | Latin small letter u with diaeresis and acute     |
 * | U+01D9  | Ǚ     | U           | Latin capital letter U with diaeresis and caron   |
 * | U+01DA  | ǚ     | u           | Latin small letter u with diaeresis and caron     |
 * | U+01DB  | Ǜ     | U           | Latin capital letter U with diaeresis and grave   |
 * | U+01DC  | ǜ     | u           | Latin small letter u with diaeresis and grave     |
 *
 * Decompositions for Latin Extended-B:
 *
 * |   Code   | Glyph | Replacement |                Description                |
 * | -------- | ----- | ----------- | ----------------------------------------- |
 * | U+0218   | Ș     | S           | Latin capital letter S with comma below   |
 * | U+0219   | ș     | s           | Latin small letter s with comma below     |
 * | U+021A   | Ț     | T           | Latin capital letter T with comma below   |
 * | U+021B   | ț     | t           | Latin small letter t with comma below     |
 *
 * Vowels with diacritic (Chinese, Hanyu Pinyin):
 *
 * |   Code   | Glyph | Replacement |                      Description                      |
 * | -------- | ----- | ----------- | ----------------------------------------------------- |
 * | U+0251   | ɑ     | a           | Latin small letter alpha                              |
 * | U+1EA0   | Ạ     | A           | Latin capital letter A with dot below                 |
 * | U+1EA1   | ạ     | a           | Latin small letter a with dot below                   |
 * | U+1EA2   | Ả     | A           | Latin capital letter A with hook above                |
 * | U+1EA3   | ả     | a           | Latin small letter a with hook above                  |
 * | U+1EA4   | Ấ     | A           | Latin capital letter A with circumflex and acute      |
 * | U+1EA5   | ấ     | a           | Latin small letter a with circumflex and acute        |
 * | U+1EA6   | Ầ     | A           | Latin capital letter A with circumflex and grave      |
 * | U+1EA7   | ầ     | a           | Latin small letter a with circumflex and grave        |
 * | U+1EA8   | Ẩ     | A           | Latin capital letter A with circumflex and hook above |
 * | U+1EA9   | ẩ     | a           | Latin small letter a with circumflex and hook above   |
 * | U+1EAA   | Ẫ     | A           | Latin capital letter A with circumflex and tilde      |
 * | U+1EAB   | ẫ     | a           | Latin small letter a with circumflex and tilde        |
 * | U+1EA6   | Ậ     | A           | Latin capital letter A with circumflex and dot below  |
 * | U+1EAD   | ậ     | a           | Latin small letter a with circumflex and dot below    |
 * | U+1EAE   | Ắ     | A           | Latin capital letter A with breve and acute           |
 * | U+1EAF   | ắ     | a           | Latin small letter a with breve and acute             |
 * | U+1EB0   | Ằ     | A           | Latin capital letter A with breve and grave           |
 * | U+1EB1   | ằ     | a           | Latin small letter a with breve and grave             |
 * | U+1EB2   | Ẳ     | A           | Latin capital letter A with breve and hook above      |
 * | U+1EB3   | ẳ     | a           | Latin small letter a with breve and hook above        |
 * | U+1EB4   | Ẵ     | A           | Latin capital letter A with breve and tilde           |
 * | U+1EB5   | ẵ     | a           | Latin small letter a with breve and tilde             |
 * | U+1EB6   | Ặ     | A           | Latin capital letter A with breve and dot below       |
 * | U+1EB7   | ặ     | a           | Latin small letter a with breve and dot below         |
 * | U+1EB8   | Ẹ     | E           | Latin capital letter E with dot below                 |
 * | U+1EB9   | ẹ     | e           | Latin small letter e with dot below                   |
 * | U+1EBA   | Ẻ     | E           | Latin capital letter E with hook above                |
 * | U+1EBB   | ẻ     | e           | Latin small letter e with hook above                  |
 * | U+1EBC   | Ẽ     | E           | Latin capital letter E with tilde                     |
 * | U+1EBD   | ẽ     | e           | Latin small letter e with tilde                       |
 * | U+1EBE   | Ế     | E           | Latin capital letter E with circumflex and acute      |
 * | U+1EBF   | ế     | e           | Latin small letter e with circumflex and acute        |
 * | U+1EC0   | Ề     | E           | Latin capital letter E with circumflex and grave      |
 * | U+1EC1   | ề     | e           | Latin small letter e with circumflex and grave        |
 * | U+1EC2   | Ể     | E           | Latin capital letter E with circumflex and hook above |
 * | U+1EC3   | ể     | e           | Latin small letter e with circumflex and hook above   |
 * | U+1EC4   | Ễ     | E           | Latin capital letter E with circumflex and tilde      |
 * | U+1EC5   | ễ     | e           | Latin small letter e with circumflex and tilde        |
 * | U+1EC6   | Ệ     | E           | Latin capital letter E with circumflex and dot below  |
 * | U+1EC7   | ệ     | e           | Latin small letter e with circumflex and dot below    |
 * | U+1EC8   | Ỉ     | I           | Latin capital letter I with hook above                |
 * | U+1EC9   | ỉ     | i           | Latin small letter i with hook above                  |
 * | U+1ECA   | Ị     | I           | Latin capital letter I with dot below                 |
 * | U+1ECB   | ị     | i           | Latin small letter i with dot below                   |
 * | U+1ECC   | Ọ     | O           | Latin capital letter O with dot below                 |
 * | U+1ECD   | ọ     | o           | Latin small letter o with dot below                   |
 * | U+1ECE   | Ỏ     | O           | Latin capital letter O with hook above                |
 * | U+1ECF   | ỏ     | o           | Latin small letter o with hook above                  |
 * | U+1ED0   | Ố     | O           | Latin capital letter O with circumflex and acute      |
 * | U+1ED1   | ố     | o           | Latin small letter o with circumflex and acute        |
 * | U+1ED2   | Ồ     | O           | Latin capital letter O with circumflex and grave      |
 * | U+1ED3   | ồ     | o           | Latin small letter o with circumflex and grave        |
 * | U+1ED4   | Ổ     | O           | Latin capital letter O with circumflex and hook above |
 * | U+1ED5   | ổ     | o           | Latin small letter o with circumflex and hook above   |
 * | U+1ED6   | Ỗ     | O           | Latin capital letter O with circumflex and tilde      |
 * | U+1ED7   | ỗ     | o           | Latin small letter o with circumflex and tilde        |
 * | U+1ED8   | Ộ     | O           | Latin capital letter O with circumflex and dot below  |
 * | U+1ED9   | ộ     | o           | Latin small letter o with circumflex and dot below    |
 * | U+1EDA   | Ớ     | O           | Latin capital letter O with horn and acute            |
 * | U+1EDB   | ớ     | o           | Latin small letter o with horn and acute              |
 * | U+1EDC   | Ờ     | O           | Latin capital letter O with horn and grave            |
 * | U+1EDD   | ờ     | o           | Latin small letter o with horn and grave              |
 * | U+1EDE   | Ở     | O           | Latin capital letter O with horn and hook above       |
 * | U+1EDF   | ở     | o           | Latin small letter o with horn and hook above         |
 * | U+1EE0   | Ỡ     | O           | Latin capital letter O with horn and tilde            |
 * | U+1EE1   | ỡ     | o           | Latin small letter o with horn and tilde              |
 * | U+1EE2   | Ợ     | O           | Latin capital letter O with horn and dot below        |
 * | U+1EE3   | ợ     | o           | Latin small letter o with horn and dot below          |
 * | U+1EE4   | Ụ     | U           | Latin capital letter U with dot below                 |
 * | U+1EE5   | ụ     | u           | Latin small letter u with dot below                   |
 * | U+1EE6   | Ủ     | U           | Latin capital letter U with hook above                |
 * | U+1EE7   | ủ     | u           | Latin small letter u with hook above                  |
 * | U+1EE8   | Ứ     | U           | Latin capital letter U with horn and acute            |
 * | U+1EE9   | ứ     | u           | Latin small letter u with horn and acute              |
 * | U+1EEA   | Ừ     | U           | Latin capital letter U with horn and grave            |
 * | U+1EEB   | ừ     | u           | Latin small letter u with horn and grave              |
 * | U+1EEC   | Ử     | U           | Latin capital letter U with horn and hook above       |
 * | U+1EED   | ử     | u           | Latin small letter u with horn and hook above         |
 * | U+1EEE   | Ữ     | U           | Latin capital letter U with horn and tilde            |
 * | U+1EEF   | ữ     | u           | Latin small letter u with horn and tilde              |
 * | U+1EF0   | Ự     | U           | Latin capital letter U with horn and dot below        |
 * | U+1EF1   | ự     | u           | Latin small letter u with horn and dot below          |
 * | U+1EF2   | Ỳ     | Y           | Latin capital letter Y with grave                     |
 * | U+1EF3   | ỳ     | y           | Latin small letter y with grave                       |
 * | U+1EF4   | Ỵ     | Y           | Latin capital letter Y with dot below                 |
 * | U+1EF5   | ỵ     | y           | Latin small letter y with dot below                   |
 * | U+1EF6   | Ỷ     | Y           | Latin capital letter Y with hook above                |
 * | U+1EF7   | ỷ     | y           | Latin small letter y with hook above                  |
 * | U+1EF8   | Ỹ     | Y           | Latin capital letter Y with tilde                     |
 * | U+1EF9   | ỹ     | y           | Latin small letter y with tilde                       |
 *
 * German (`de_DE`), German formal (`de_DE_formal`), German (Switzerland) formal (`de_CH`),
 * and German (Switzerland) informal (`de_CH_informal`) locales:
 *
 * |   Code   | Glyph | Replacement |               Description               |
 * | -------- | ----- | ----------- | --------------------------------------- |
 * | U+00C4   | Ä     | Ae          | Latin capital letter A with diaeresis   |
 * | U+00E4   | ä     | ae          | Latin small letter a with diaeresis     |
 * | U+00D6   | Ö     | Oe          | Latin capital letter O with diaeresis   |
 * | U+00F6   | ö     | oe          | Latin small letter o with diaeresis     |
 * | U+00DC   | Ü     | Ue          | Latin capital letter U with diaeresis   |
 * | U+00FC   | ü     | ue          | Latin small letter u with diaeresis     |
 * | U+00DF   | ß     | ss          | Latin small letter sharp s              |
 *
 * Danish (`da_DK`) locale:
 *
 * |   Code   | Glyph | Replacement |               Description               |
 * | -------- | ----- | ----------- | --------------------------------------- |
 * | U+00C6   | Æ     | Ae          | Latin capital letter AE                 |
 * | U+00E6   | æ     | ae          | Latin small letter ae                   |
 * | U+00D8   | Ø     | Oe          | Latin capital letter O with stroke      |
 * | U+00F8   | ø     | oe          | Latin small letter o with stroke        |
 * | U+00C5   | Å     | Aa          | Latin capital letter A with ring above  |
 * | U+00E5   | å     | aa          | Latin small letter a with ring above    |
 *
 * Catalan (`ca`) locale:
 *
 * |   Code   | Glyph | Replacement |               Description               |
 * | -------- | ----- | ----------- | --------------------------------------- |
 * | U+00B7   | l·l   | ll          | Flown dot (between two Ls)              |
 *
 * Serbian (`sr_RS`) and Bosnian (`bs_BA`) locales:
 *
 * |   Code   | Glyph | Replacement |               Description               |
 * | -------- | ----- | ----------- | --------------------------------------- |
 * | U+0110   | Đ     | DJ          | Latin capital letter D with stroke      |
 * | U+0111   | đ     | dj          | Latin small letter d with stroke        |
 *
 * @since 1.2.1
 * @since 4.6.0 Added locale support for `de_CH`, `de_CH_informal`, and `ca`.
 * @since 4.7.0 Added locale support for `sr_RS`.
 * @since 4.8.0 Added locale support for `bs_BA`.
 *
 * @param string $string Text that might have accent characters
 * @return string Filtered string with replaced "nice" characters.
 */
function remove_accents( $string ) {
  if ( !preg_match('/[\x80-\xff]/', $string) )
    return $string;

  if (seems_utf8($string)) {
    $chars = array(
    // Decompositions for Latin-1 Supplement
    'ª' => 'a', 'º' => 'o',
    'À' => 'A', 'Á' => 'A',
    'Â' => 'A', 'Ã' => 'A',
    'Ä' => 'A', 'Å' => 'A',
    'Æ' => 'AE','Ç' => 'C',
    'È' => 'E', 'É' => 'E',
    'Ê' => 'E', 'Ë' => 'E',
    'Ì' => 'I', 'Í' => 'I',
    'Î' => 'I', 'Ï' => 'I',
    'Ð' => 'D', 'Ñ' => 'N',
    'Ò' => 'O', 'Ó' => 'O',
    'Ô' => 'O', 'Õ' => 'O',
    'Ö' => 'O', 'Ù' => 'U',
    'Ú' => 'U', 'Û' => 'U',
    'Ü' => 'U', 'Ý' => 'Y',
    'Þ' => 'TH','ß' => 's',
    'à' => 'a', 'á' => 'a',
    'â' => 'a', 'ã' => 'a',
    'ä' => 'a', 'å' => 'a',
    'æ' => 'ae','ç' => 'c',
    'è' => 'e', 'é' => 'e',
    'ê' => 'e', 'ë' => 'e',
    'ì' => 'i', 'í' => 'i',
    'î' => 'i', 'ï' => 'i',
    'ð' => 'd', 'ñ' => 'n',
    'ò' => 'o', 'ó' => 'o',
    'ô' => 'o', 'õ' => 'o',
    'ö' => 'o', 'ø' => 'o',
    'ù' => 'u', 'ú' => 'u',
    'û' => 'u', 'ü' => 'u',
    'ý' => 'y', 'þ' => 'th',
    'ÿ' => 'y', 'Ø' => 'O',
    // Decompositions for Latin Extended-A
    'Ā' => 'A', 'ā' => 'a',
    'Ă' => 'A', 'ă' => 'a',
    'Ą' => 'A', 'ą' => 'a',
    'Ć' => 'C', 'ć' => 'c',
    'Ĉ' => 'C', 'ĉ' => 'c',
    'Ċ' => 'C', 'ċ' => 'c',
    'Č' => 'C', 'č' => 'c',
    'Ď' => 'D', 'ď' => 'd',
    'Đ' => 'D', 'đ' => 'd',
    'Ē' => 'E', 'ē' => 'e',
    'Ĕ' => 'E', 'ĕ' => 'e',
    'Ė' => 'E', 'ė' => 'e',
    'Ę' => 'E', 'ę' => 'e',
    'Ě' => 'E', 'ě' => 'e',
    'Ĝ' => 'G', 'ĝ' => 'g',
    'Ğ' => 'G', 'ğ' => 'g',
    'Ġ' => 'G', 'ġ' => 'g',
    'Ģ' => 'G', 'ģ' => 'g',
    'Ĥ' => 'H', 'ĥ' => 'h',
    'Ħ' => 'H', 'ħ' => 'h',
    'Ĩ' => 'I', 'ĩ' => 'i',
    'Ī' => 'I', 'ī' => 'i',
    'Ĭ' => 'I', 'ĭ' => 'i',
    'Į' => 'I', 'į' => 'i',
    'İ' => 'I', 'ı' => 'i',
    'Ĳ' => 'IJ','ĳ' => 'ij',
    'Ĵ' => 'J', 'ĵ' => 'j',
    'Ķ' => 'K', 'ķ' => 'k',
    'ĸ' => 'k', 'Ĺ' => 'L',
    'ĺ' => 'l', 'Ļ' => 'L',
    'ļ' => 'l', 'Ľ' => 'L',
    'ľ' => 'l', 'Ŀ' => 'L',
    'ŀ' => 'l', 'Ł' => 'L',
    'ł' => 'l', 'Ń' => 'N',
    'ń' => 'n', 'Ņ' => 'N',
    'ņ' => 'n', 'Ň' => 'N',
    'ň' => 'n', 'ŉ' => 'n',
    'Ŋ' => 'N', 'ŋ' => 'n',
    'Ō' => 'O', 'ō' => 'o',
    'Ŏ' => 'O', 'ŏ' => 'o',
    'Ő' => 'O', 'ő' => 'o',
    'Œ' => 'OE','œ' => 'oe',
    'Ŕ' => 'R','ŕ' => 'r',
    'Ŗ' => 'R','ŗ' => 'r',
    'Ř' => 'R','ř' => 'r',
    'Ś' => 'S','ś' => 's',
    'Ŝ' => 'S','ŝ' => 's',
    'Ş' => 'S','ş' => 's',
    'Š' => 'S', 'š' => 's',
    'Ţ' => 'T', 'ţ' => 't',
    'Ť' => 'T', 'ť' => 't',
    'Ŧ' => 'T', 'ŧ' => 't',
    'Ũ' => 'U', 'ũ' => 'u',
    'Ū' => 'U', 'ū' => 'u',
    'Ŭ' => 'U', 'ŭ' => 'u',
    'Ů' => 'U', 'ů' => 'u',
    'Ű' => 'U', 'ű' => 'u',
    'Ų' => 'U', 'ų' => 'u',
    'Ŵ' => 'W', 'ŵ' => 'w',
    'Ŷ' => 'Y', 'ŷ' => 'y',
    'Ÿ' => 'Y', 'Ź' => 'Z',
    'ź' => 'z', 'Ż' => 'Z',
    'ż' => 'z', 'Ž' => 'Z',
    'ž' => 'z', 'ſ' => 's',
    // Decompositions for Latin Extended-B
    'Ș' => 'S', 'ș' => 's',
    'Ț' => 'T', 'ț' => 't',
    // Euro Sign
    '€' => 'E',
    // GBP (Pound) Sign
    '£' => '',
    // Vowels with diacritic (Vietnamese)
    // unmarked
    'Ơ' => 'O', 'ơ' => 'o',
    'Ư' => 'U', 'ư' => 'u',
    // grave accent
    'Ầ' => 'A', 'ầ' => 'a',
    'Ằ' => 'A', 'ằ' => 'a',
    'Ề' => 'E', 'ề' => 'e',
    'Ồ' => 'O', 'ồ' => 'o',
    'Ờ' => 'O', 'ờ' => 'o',
    'Ừ' => 'U', 'ừ' => 'u',
    'Ỳ' => 'Y', 'ỳ' => 'y',
    // hook
    'Ả' => 'A', 'ả' => 'a',
    'Ẩ' => 'A', 'ẩ' => 'a',
    'Ẳ' => 'A', 'ẳ' => 'a',
    'Ẻ' => 'E', 'ẻ' => 'e',
    'Ể' => 'E', 'ể' => 'e',
    'Ỉ' => 'I', 'ỉ' => 'i',
    'Ỏ' => 'O', 'ỏ' => 'o',
    'Ổ' => 'O', 'ổ' => 'o',
    'Ở' => 'O', 'ở' => 'o',
    'Ủ' => 'U', 'ủ' => 'u',
    'Ử' => 'U', 'ử' => 'u',
    'Ỷ' => 'Y', 'ỷ' => 'y',
    // tilde
    'Ẫ' => 'A', 'ẫ' => 'a',
    'Ẵ' => 'A', 'ẵ' => 'a',
    'Ẽ' => 'E', 'ẽ' => 'e',
    'Ễ' => 'E', 'ễ' => 'e',
    'Ỗ' => 'O', 'ỗ' => 'o',
    'Ỡ' => 'O', 'ỡ' => 'o',
    'Ữ' => 'U', 'ữ' => 'u',
    'Ỹ' => 'Y', 'ỹ' => 'y',
    // acute accent
    'Ấ' => 'A', 'ấ' => 'a',
    'Ắ' => 'A', 'ắ' => 'a',
    'Ế' => 'E', 'ế' => 'e',
    'Ố' => 'O', 'ố' => 'o',
    'Ớ' => 'O', 'ớ' => 'o',
    'Ứ' => 'U', 'ứ' => 'u',
    // dot below
    'Ạ' => 'A', 'ạ' => 'a',
    'Ậ' => 'A', 'ậ' => 'a',
    'Ặ' => 'A', 'ặ' => 'a',
    'Ẹ' => 'E', 'ẹ' => 'e',
    'Ệ' => 'E', 'ệ' => 'e',
    'Ị' => 'I', 'ị' => 'i',
    'Ọ' => 'O', 'ọ' => 'o',
    'Ộ' => 'O', 'ộ' => 'o',
    'Ợ' => 'O', 'ợ' => 'o',
    'Ụ' => 'U', 'ụ' => 'u',
    'Ự' => 'U', 'ự' => 'u',
    'Ỵ' => 'Y', 'ỵ' => 'y',
    // Vowels with diacritic (Chinese, Hanyu Pinyin)
    'ɑ' => 'a',
    // macron
    'Ǖ' => 'U', 'ǖ' => 'u',
    // acute accent
    'Ǘ' => 'U', 'ǘ' => 'u',
    // caron
    'Ǎ' => 'A', 'ǎ' => 'a',
    'Ǐ' => 'I', 'ǐ' => 'i',
    'Ǒ' => 'O', 'ǒ' => 'o',
    'Ǔ' => 'U', 'ǔ' => 'u',
    'Ǚ' => 'U', 'ǚ' => 'u',
    // grave accent
    'Ǜ' => 'U', 'ǜ' => 'u',
    );

    // Used for locale-specific rules
    // $locale = get_locale();
    $locale = 'fr_CA';

    if ( 'de_DE' == $locale || 'de_DE_formal' == $locale || 'de_CH' == $locale || 'de_CH_informal' == $locale ) {
      $chars[ 'Ä' ] = 'Ae';
      $chars[ 'ä' ] = 'ae';
      $chars[ 'Ö' ] = 'Oe';
      $chars[ 'ö' ] = 'oe';
      $chars[ 'Ü' ] = 'Ue';
      $chars[ 'ü' ] = 'ue';
      $chars[ 'ß' ] = 'ss';
    } elseif ( 'da_DK' === $locale ) {
      $chars[ 'Æ' ] = 'Ae';
      $chars[ 'æ' ] = 'ae';
      $chars[ 'Ø' ] = 'Oe';
      $chars[ 'ø' ] = 'oe';
      $chars[ 'Å' ] = 'Aa';
      $chars[ 'å' ] = 'aa';
    } elseif ( 'ca' === $locale ) {
      $chars[ 'l·l' ] = 'll';
    } elseif ( 'sr_RS' === $locale || 'bs_BA' === $locale ) {
      $chars[ 'Đ' ] = 'DJ';
      $chars[ 'đ' ] = 'dj';
    }

    $string = strtr($string, $chars);
  } else {
    $chars = array();
    // Assume ISO-8859-1 if not UTF-8
    $chars['in'] = "\x80\x83\x8a\x8e\x9a\x9e"
      ."\x9f\xa2\xa5\xb5\xc0\xc1\xc2"
      ."\xc3\xc4\xc5\xc7\xc8\xc9\xca"
      ."\xcb\xcc\xcd\xce\xcf\xd1\xd2"
      ."\xd3\xd4\xd5\xd6\xd8\xd9\xda"
      ."\xdb\xdc\xdd\xe0\xe1\xe2\xe3"
      ."\xe4\xe5\xe7\xe8\xe9\xea\xeb"
      ."\xec\xed\xee\xef\xf1\xf2\xf3"
      ."\xf4\xf5\xf6\xf8\xf9\xfa\xfb"
      ."\xfc\xfd\xff";

    $chars['out'] = "EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy";

    $string = strtr($string, $chars['in'], $chars['out']);
    $double_chars = array();
    $double_chars['in'] = array("\x8c", "\x9c", "\xc6", "\xd0", "\xde", "\xdf", "\xe6", "\xf0", "\xfe");
    $double_chars['out'] = array('OE', 'oe', 'AE', 'DH', 'TH', 'ss', 'ae', 'dh', 'th');
    $string = str_replace($double_chars['in'], $double_chars['out'], $string);
  }

  return $string;
}

/**
 * Sanitizes a title, replacing whitespace and a few other characters with dashes.
 *
 * Limits the output to alphanumeric characters, underscore (_) and dash (-).
 * Whitespace becomes a dash.
 *
 * @since 1.2.0
 *
 * @param string $title     The title to be sanitized.
 * @param string $raw_title Optional. Not used.
 * @param string $context   Optional. The operation for which the string is sanitized.
 * @return string The sanitized title.
 */
function sanitize_title_with_dashes( $title, $raw_title = '', $context = 'display' ) {
  $title = strip_tags($title);
  // Preserve escaped octets.
  $title = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '---$1---', $title);
  // Remove percent signs that are not part of an octet.
  $title = str_replace('%', '', $title);
  // Restore octets.
  $title = preg_replace('|---([a-fA-F0-9][a-fA-F0-9])---|', '%$1', $title);

  if (seems_utf8($title)) {
    if (function_exists('mb_strtolower')) {
      $title = mb_strtolower($title, 'UTF-8');
    }
    $title = utf8_uri_encode($title, 200);
  }

  $title = strtolower($title);

  if ( 'save' == $context ) {
    // Convert nbsp, ndash and mdash to hyphens
    $title = str_replace( array( '%c2%a0', '%e2%80%93', '%e2%80%94' ), '-', $title );
    // Convert nbsp, ndash and mdash HTML entities to hyphens
    $title = str_replace( array( '&nbsp;', '&#160;', '&ndash;', '&#8211;', '&mdash;', '&#8212;' ), '-', $title );

    // Strip these characters entirely
    $title = str_replace( array(
      // iexcl and iquest
      '%c2%a1', '%c2%bf',
      // angle quotes
      '%c2%ab', '%c2%bb', '%e2%80%b9', '%e2%80%ba',
      // curly quotes
      '%e2%80%98', '%e2%80%99', '%e2%80%9c', '%e2%80%9d',
      '%e2%80%9a', '%e2%80%9b', '%e2%80%9e', '%e2%80%9f',
      // copy, reg, deg, hellip and trade
      '%c2%a9', '%c2%ae', '%c2%b0', '%e2%80%a6', '%e2%84%a2',
      // acute accents
      '%c2%b4', '%cb%8a', '%cc%81', '%cd%81',
      // grave accent, macron, caron
      '%cc%80', '%cc%84', '%cc%8c',
    ), '', $title );

    // Convert times to x
    $title = str_replace( '%c3%97', 'x', $title );
  }

  $title = preg_replace('/&.+?;/', '', $title); // kill entities
  $title = str_replace('.', '-', $title);

  $title = preg_replace('/[^%a-z0-9 _-]/', '', $title);
  $title = preg_replace('/\s+/', '-', $title);
  $title = preg_replace('|-+|', '-', $title);
  $title = trim($title, '-');

  return $title;
}


/**
 * Sanitizes a title, or returns a fallback title.
 *
 * Specifically, HTML and PHP tags are stripped. Further actions can be added
 * via the plugin API. If $title is empty and $fallback_title is set, the latter
 * will be used.
 *
 * @since 1.0.0
 *
 * @param string $title          The string to be sanitized.
 * @param string $fallback_title Optional. A title to use if $title is empty.
 * @param string $context        Optional. The operation for which the string is sanitized
 * @return string The sanitized string.
 */
function sanitize_title( $title, $fallback_title = '', $context = 'save' ) {
  $raw_title = $title;

  if ( 'save' == $context )
    $title = remove_accents($title);

  /**
   * Filters a sanitized title string.
   *
   * @since 1.2.0
   *
   * @param string $title     Sanitized title.
   * @param string $raw_title The title prior to sanitization.
   * @param string $context   The context for which the title is being sanitized.
   */
  $title = sanitize_title_with_dashes($title, $raw_title, $context);
  // $title = apply_filters( 'sanitize_title', $title, $raw_title, $context );

  if ( '' === $title || false === $title )
    $title = $fallback_title;

  return $title;
}

if($argv[1] == '--files') {
  unset($argv[0]);
  foreach($argv as $f){
      $filename = mb_convert_encoding(basename($f), 'UTF-8', 'auto');
    
      $query = $filename;

      $parts = explode('.', $query);
      foreach($parts as $index => $part){
          $parts[$index] = sanitize_title(mb_convert_encoding($part, 'UTF-8', 'auto'), '', 'save');
      }
      $my_slug = implode('.', $parts);
      $my_slug = dirname($f) . DIRECTORY_SEPARATOR . $my_slug;

      rename($f, $my_slug);
  }
}else{
  $query = $argv[1];

  $parts = explode('.', $query);
  foreach($parts as $index => $part){
      $parts[$index] = sanitize_title(mb_convert_encoding($part, 'UTF-8', 'auto'), '', 'save');
  }
  $my_slug = implode('.', $parts);

  echo json_encode(array(
      'items' => array(
          array(
              // 'uid'=>'slug',
              'type'=>'default',
              'title'=>'Slugify',
              'subtitle'=>$my_slug,
              'arg'=>$my_slug,
              'text'=>array(
                  'copy'=>$my_slug,
                  'large_type'=>$my_slug
              )
          )
      ),
      "variables" => array(
          'slug'=>$my_slug
      )
  ));
}

?>