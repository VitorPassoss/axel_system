<?php

/**
 * Bracket.php
 *
 * @since       2011-05-23
 * @category    Library
 * @package     UnicodeData
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2011-2024 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-unicode-data
 *
 * This file is part of tc-lib-unicode-data software library.
 */

namespace Com\Tecnick\Unicode\Data;

/**
 * Com\Tecnick\Unicode\Data\Bracket
 *
 * @since       2011-05-23
 * @category    Library
 * @package     UnicodeData
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2011-2024 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-unicode-data
 */
class Bracket
{
    /**
     * Bracket unicode characters (open bracket code => close bracket code).
     *
     * @var array<int, int>
     */
    public const OPEN = [
        0x0028 => 0x0029, // PARENTHESIS
        0x005B => 0x005D, // SQUARE BRACKET
        0x007B => 0x007D, // CURLY BRACKET
        0x0F3A => 0x0F3B, // TIBETAN MARK GUG RTAGS GYON
        0x0F3C => 0x0F3D, // TIBETAN MARK ANG KHANG GYON
        0x169B => 0x169C, // OGHAM FEATHER MARK
        0x2045 => 0x2046, // SQUARE BRACKET WITH QUILL
        0x207D => 0x207E, // SUPERSCRIPT PARENTHESIS
        0x208D => 0x208E, // SUBSCRIPT PARENTHESIS
        0x2308 => 0x2309, // CEILING
        0x230A => 0x230B, // FLOOR
        0x2329 => 0x232A, // POINTING ANGLE BRACKET
        0x2768 => 0x2769, // MEDIUM PARENTHESIS ORNAMENT
        0x276A => 0x276B, // MEDIUM FLATTENED PARENTHESIS ORNAMENT
        0x276C => 0x276D, // MEDIUM POINTING ANGLE BRACKET ORNAMENT
        0x276E => 0x276F, // HEAVY POINTING ANGLE QUOTATION MARK ORNAMENT
        0x2770 => 0x2771, // HEAVY POINTING ANGLE BRACKET ORNAMENT
        0x2772 => 0x2773, // LIGHT TORTOISE SHELL BRACKET ORNAMENT
        0x2774 => 0x2775, // MEDIUM CURLY BRACKET ORNAMENT
        0x27C5 => 0x27C6, // S-SHAPED BAG DELIMITER
        0x27E6 => 0x27E7, // MATHEMATICAL WHITE SQUARE BRACKET
        0x27E8 => 0x27E9, // MATHEMATICAL ANGLE BRACKET
        0x27EA => 0x27EB, // MATHEMATICAL DOUBLE ANGLE BRACKET
        0x27EC => 0x27ED, // MATHEMATICAL WHITE TORTOISE SHELL BRACKET
        0x27EE => 0x27EF, // MATHEMATICAL FLATTENED PARENTHESIS
        0x2983 => 0x2984, // WHITE CURLY BRACKET
        0x2985 => 0x2986, // WHITE PARENTHESIS
        0x2987 => 0x2988, // Z NOTATION IMAGE BRACKET
        0x2989 => 0x298A, // Z NOTATION BINDING BRACKET
        0x298B => 0x298C, // SQUARE BRACKET WITH UNDERBAR
        0x298D => 0x2990, // SQUARE BRACKET WITH TICK IN TOP CORNER
        0x298F => 0x298E, // SQUARE BRACKET WITH TICK IN BOTTOM CORNER
        0x2991 => 0x2992, // ANGLE BRACKET WITH DOT
        0x2993 => 0x2994, // ARC LESS-THAN BRACKET
        0x2995 => 0x2996, // DOUBLE ARC GREATER-THAN BRACKET
        0x2997 => 0x2998, // BLACK TORTOISE SHELL BRACKET
        0x29D8 => 0x29D9, // WIGGLY FENCE
        0x29DA => 0x29DB, // DOUBLE WIGGLY FENCE
        0x29FC => 0x29FD, // POINTING CURVED ANGLE BRACKET
        0x2E22 => 0x2E23, // TOP HALF BRACKET
        0x2E24 => 0x2E25, // BOTTOM HALF BRACKET
        0x2E26 => 0x2E27, // SIDEWAYS U BRACKET
        0x2E28 => 0x2E29, // DOUBLE PARENTHESIS
        0x3008 => 0x3009, // ANGLE BRACKET
        0x300A => 0x300B, // DOUBLE ANGLE BRACKET
        0x300C => 0x300D, // CORNER BRACKET
        0x300E => 0x300F, // WHITE CORNER BRACKET
        0x3010 => 0x3011, // BLACK LENTICULAR BRACKET
        0x3014 => 0x3015, // TORTOISE SHELL BRACKET
        0x3016 => 0x3017, // WHITE LENTICULAR BRACKET
        0x3018 => 0x3019, // WHITE TORTOISE SHELL BRACKET
        0x301A => 0x301B, // WHITE SQUARE BRACKET
        0xFE59 => 0xFE5A, // SMALL PARENTHESIS
        0xFE5B => 0xFE5C, // SMALL CURLY BRACKET
        0xFE5D => 0xFE5E, // SMALL TORTOISE SHELL BRACKET
        0xFF08 => 0xFF09, // FULLWIDTH PARENTHESIS
        0xFF3B => 0xFF3D, // FULLWIDTH SQUARE BRACKET
        0xFF5B => 0xFF5D, // FULLWIDTH CURLY BRACKET
        0xFF5F => 0xFF60, // FULLWIDTH WHITE PARENTHESIS
        0xFF62 => 0xFF63,  // HALFWIDTH CORNER BRACKET
    ];

    /**
     * Bracket unicode characters (close bracket code => open bracket code).
     *
     * @var array<int, int>
     */
    public const CLOSE = [
        0x0029 => 0x0028, // PARENTHESIS
        0x005D => 0x005B, // SQUARE BRACKET
        0x007D => 0x007B, // CURLY BRACKET
        0x0F3B => 0x0F3A, // TIBETAN MARK GUG RTAGS GYON
        0x0F3D => 0x0F3C, // TIBETAN MARK ANG KHANG GYON
        0x169C => 0x169B, // OGHAM FEATHER MARK
        0x2046 => 0x2045, // SQUARE BRACKET WITH QUILL
        0x207E => 0x207D, // SUPERSCRIPT PARENTHESIS
        0x208E => 0x208D, // SUBSCRIPT PARENTHESIS
        0x2309 => 0x2308, // CEILING
        0x230B => 0x230A, // FLOOR
        0x232A => 0x2329, // POINTING ANGLE BRACKET
        0x2769 => 0x2768, // MEDIUM PARENTHESIS ORNAMENT
        0x276B => 0x276A, // MEDIUM FLATTENED PARENTHESIS ORNAMENT
        0x276D => 0x276C, // MEDIUM POINTING ANGLE BRACKET ORNAMENT
        0x276F => 0x276E, // HEAVY POINTING ANGLE QUOTATION MARK ORNAMENT
        0x2771 => 0x2770, // HEAVY POINTING ANGLE BRACKET ORNAMENT
        0x2773 => 0x2772, // LIGHT TORTOISE SHELL BRACKET ORNAMENT
        0x2775 => 0x2774, // MEDIUM CURLY BRACKET ORNAMENT
        0x27C6 => 0x27C5, // S-SHAPED BAG DELIMITER
        0x27E7 => 0x27E6, // MATHEMATICAL WHITE SQUARE BRACKET
        0x27E9 => 0x27E8, // MATHEMATICAL ANGLE BRACKET
        0x27EB => 0x27EA, // MATHEMATICAL DOUBLE ANGLE BRACKET
        0x27ED => 0x27EC, // MATHEMATICAL WHITE TORTOISE SHELL BRACKET
        0x27EF => 0x27EE, // MATHEMATICAL FLATTENED PARENTHESIS
        0x2984 => 0x2983, // WHITE CURLY BRACKET
        0x2986 => 0x2985, // WHITE PARENTHESIS
        0x2988 => 0x2987, // Z NOTATION IMAGE BRACKET
        0x298A => 0x2989, // Z NOTATION BINDING BRACKET
        0x298C => 0x298B, // SQUARE BRACKET WITH UNDERBAR
        0x2990 => 0x298D, // SQUARE BRACKET WITH TICK IN TOP CORNER
        0x298E => 0x298F, // SQUARE BRACKET WITH TICK IN BOTTOM CORNER
        0x2992 => 0x2991, // ANGLE BRACKET WITH DOT
        0x2994 => 0x2993, // ARC LESS-THAN BRACKET
        0x2996 => 0x2995, // DOUBLE ARC GREATER-THAN BRACKET
        0x2998 => 0x2997, // BLACK TORTOISE SHELL BRACKET
        0x29D9 => 0x29D8, // WIGGLY FENCE
        0x29DB => 0x29DA, // DOUBLE WIGGLY FENCE
        0x29FD => 0x29FC, // POINTING CURVED ANGLE BRACKET
        0x2E23 => 0x2E22, // TOP HALF BRACKET
        0x2E25 => 0x2E24, // BOTTOM HALF BRACKET
        0x2E27 => 0x2E26, // SIDEWAYS U BRACKET
        0x2E29 => 0x2E28, // DOUBLE PARENTHESIS
        0x3009 => 0x3008, // ANGLE BRACKET
        0x300B => 0x300A, // DOUBLE ANGLE BRACKET
        0x300D => 0x300C, // CORNER BRACKET
        0x300F => 0x300E, // WHITE CORNER BRACKET
        0x3011 => 0x3010, // BLACK LENTICULAR BRACKET
        0x3015 => 0x3014, // TORTOISE SHELL BRACKET
        0x3017 => 0x3016, // WHITE LENTICULAR BRACKET
        0x3019 => 0x3018, // WHITE TORTOISE SHELL BRACKET
        0x301B => 0x301A, // WHITE SQUARE BRACKET
        0xFE5A => 0xFE59, // SMALL PARENTHESIS
        0xFE5C => 0xFE5B, // SMALL CURLY BRACKET
        0xFE5E => 0xFE5D, // SMALL TORTOISE SHELL BRACKET
        0xFF09 => 0xFF08, // FULLWIDTH PARENTHESIS
        0xFF3D => 0xFF3B, // FULLWIDTH SQUARE BRACKET
        0xFF5D => 0xFF5B, // FULLWIDTH CURLY BRACKET
        0xFF60 => 0xFF5F, // FULLWIDTH WHITE PARENTHESIS
        0xFF63 => 0xFF62,  // HALFWIDTH CORNER BRACKET
    ];
}
