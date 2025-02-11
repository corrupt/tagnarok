<?php

require dirname(__FILE__) . "/../../php/vendor/autoload.php";

use corrupt\tagnarok\Parser;

$parser = new Parser();

$text = "123 aaa [band]Emperor[/band] [album id=1234]This is a [b][i]test[/i][/b]! oh yeah![/album] [band id=123] [b]bold[/b] [/b] hello";

$ast = $parser->parse($text);

//print_r($ast);
echo json_encode($ast);