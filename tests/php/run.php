<?php

require dirname(__FILE__) . "/../../php/vendor/autoload.php";

use corrupt\tagnarok\Parser;

$parser = new Parser();

//$text = "123 aaa [band]Emperor[/band] [album id=666]This is a [b][i]test[/i][/b]! oh yeah![/album] [band id=123] [b]bold[/b] [/b] hello";

$text = "123 aaa [band]Emperor[/band] [album id=666]This is a [b][i]test[/i][/b]! oh yeah![/album]";

$ast = $parser->parse($text);

//print_r($ast);
$test = json_encode($ast);

echo $test;