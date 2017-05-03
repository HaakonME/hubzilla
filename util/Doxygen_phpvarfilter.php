<?php
/**
 * @file Doxygen_phpvarfilter.php
 * @brief A Doxygen INPUT_FILTER to parse \@var member variable documentation.
 *
 * An input filter for Doxygen to parse \@var class member variable documentation,
 * so it is a bit more compatible how anybody else interpretes it.
 *
 * @see http://stackoverflow.com/questions/4325224/doxygen-how-to-describe-class-member-variables-in-php/8472180#8472180
 */

$source = file_get_contents($argv[1]);

$regexp = '#\@var\s+([^\s]+)([^/]+)/\s+(var|public|protected|private)\s+(\$[^\s;=]+)#';
$replac = '${2} */ ${3} ${1} ${4}';
$source = preg_replace($regexp, $replac, $source);

echo $source;
