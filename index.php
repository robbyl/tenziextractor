
<form action="" method="post">
    <p>Import song titles <button name="titles" value="titles">Upload</button></p>
    <p>Import verses <button name="verses" value="verses">Upload</button></p>
</form>

<?php
error_reporting(E_ALL & ~E_NOTICE);
include './simple_html_dom.php';

$db = new MyDB();

if (!empty($_POST['titles'])) {

    $db->exec("DROP TABLE IF EXISTS keys");
    $sql = 'CREATE TABLE "keys" (
  "title_no" integer PRIMARY KEY NOT NULL,
  "title" text NOT NULL)';

    $verseQ = "";
    $db->exec($sql);

    echo "Importing song titles <br/>";

    $html = file_get_html("C:/xampp/htdocs/tenziextractor/index.html");

    foreach ($html->find('ul.book_open') as $ul) {
        foreach ($ul->find('a') as $li) {
            $wacha = str_get_html($li);
            $trimed = trim($wacha->plaintext);

            $title_explode = explode("-", $trimed);
            $title_no = $title_explode[1];
            $title = $title_explode[0];

//            echo $title_no . " " . $title . "<br/>";
            $verseQ = $verseQ . "('{$title_no}','{$title}'),";
        }
    }

    $build_titles = "INSERT INTO keys (title_no, title) VALUES " . substr(trim($verseQ), 0, -1);

    $db->exec($build_titles);

    echo "<br /> Import song titles finished";
}

if (!empty($_POST['verses'])) {

    $db->exec("DROP TABLE IF EXISTS verses");
    $sql = 'CREATE TABLE "verses" (
  "id" INTEGER ZEROFILL PRIMARY KEY NOT NULL,
  "title_no" integer NOT NULL,
  "verse_no" integer NOT NULL,
  "verse_text" text NOT NULL)';

    $db->exec($sql);

    echo "Import verses to SQLite db <br/>";

    $path = realpath('C:/xampp/htdocs/tenziextractor/tenzi');
    $iterator = new DirectoryIterator($path);
//    $iterator->setFlags(DirectoryIterator::SKIP_DOTS);
    $objects = new IteratorIterator($iterator);

    $part = "";

    foreach ($objects as $name) {
        if ($name->isFile()) {

            $title_no = filter_var($name, FILTER_SANITIZE_NUMBER_INT);

            $file_path = $path . '\\' . $name;

            $html = file_get_html($file_path);

            foreach ($html->find('#tenzi > p') as $element) {
                
                $index = 1;
                
                $verse_text = str_get_html($element->innertext);
                
                if (!empty($verse_text)) {
                    
                            $verse_no = $index;
                            $verse_id = $title_no.str_pad($verse_no, 3, "0", STR_PAD_LEFT); 
                            
                            $part = $part . "({$verse_id}, {$title_no}, {$verse_no}, {$verse_text}),";
                            

//                    for ($index = 0; $index < count($final); $index++) {
//                        echo ($index + 1) . " " . $final[$index] . "<br/>";
//                        $key = $index + 1;
//                        $book1 = str_pad($book, 2, "0", STR_PAD_LEFT);
//                        $chapter1 = str_pad($chapter, 3, "0", STR_PAD_LEFT);
//                        $key1 = str_pad($key, 3, "0", STR_PAD_LEFT);
//
//                        $trimed_verse = trim($final[$index]);
//                        $part = $part . "({$book1}{$chapter1}{$key1},{$book}, {$chapter}, {$key}, \"{$trimed_verse}\"),";
//                    }
                }
                
                
            }
            echo $part;
            exit;
        }
    }

    exit;

    $build_query = "INSERT INTO verses (id, title_no, verse_no, verse_text) VALUES " . substr(trim($part), 0, -1);
    $db->exec($build_query);

    echo "Importing verses finished";
}

class MyDB extends SQLite3 {

    function __construct() {
        $this->open('tenzi.db');
    }

}
?>
