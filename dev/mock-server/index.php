<?php

$content = file_get_contents('php://input');
file_put_contents('lot.txt', $content);
if (!empty($_POST)) {
//	file_put_contents('lot.txt', print_r($_POST, true));
}else {
//	file_put_contents('lot.txt', 'empty');
}


