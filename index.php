
<form action="" method="post">
    <p>Import song titles <button name="titles" value="titles">Upload</button></p>
    <p>Import verses <button name="verses" value="verses">Upload</button></p>
</form>

<?php
error_reporting(E_ALL & ~E_NOTICE);
include './simple_html_dom.php';

$db = new MyDB();

if (!empty($_POST['titles'])) {

    $has_audio = array(10, 101, 102, 103, 106, 108, 11, 112, 113, 116, 117, 118,
        121, 125, 126, 129, 13, 130, 135, 136, 14, 15, 16, 17, 19, 2, 23, 24, 25,
        26, 28, 29, 3, 31, 34, 36, 4, 40, 41, 42, 43, 44, 45, 46, 47, 49, 50, 51,
        52, 54, 56, 57, 58, 59, 6, 60, 61, 64, 68, 69, 7, 70, 71, 74, 75, 76, 77,
        78, 8, 80, 82, 84, 85, 86, 87, 88, 89, 9, 90, 91, 92, 93, 94, 95, 96, 97);

    $number_color = array(
        "#bd4444", "#499067", "#eb9736", "#b74a8e", "#399ca4", "#9f6acc"
    );

    $bg_color = array(
        "#fad2cf", "#d5ebdf", "#feefc3", "#fdcfe8", "#cbf0f8", "#e9d2fd"
    );

    $db->exec("DROP TABLE IF EXISTS songs");
    $sql = 'CREATE TABLE "songs" (
  "title_no" INTEGER PRIMARY KEY NOT NULL,
  "title" TEXT NOT NULL,
  "has_audio" TINYINT DEFAULT 0,
  "number_color" TEXT NOT NULL,
  "number_bg_color" TEXT NOT NULL)';

    $verseQ = "";
    $db->exec($sql);

    echo "Importing song titles <br/>";

    $html = file_get_html("C:/xampp-backup/htdocs/tenziextractor/index.html");

    foreach ($html->find('ul.book_open') as $ul) {
        foreach ($ul->find('a') as $li) {
            $wacha = str_get_html($li);
            $trimed = trim($wacha->plaintext);

            $title_explode = explode("-", $trimed);
            $title_no = $title_explode[1];
            $title = $title_explode[0];

//            echo $title_no . " " . $title . "<br/>";
            $m = rand(0, 5);
            $verseQ = $verseQ . '("' . $title_no . '","' . $title . '",'
                    . (in_array($title_no, $has_audio) ? 1 : 0) . ',"'
                    . $number_color[$m] . '","'
                    . $bg_color[$m] . '"' . '),';
        }
    }

    $build_titles = "INSERT INTO songs (title_no, title, has_audio, number_color, number_bg_color) VALUES " . substr(trim($verseQ), 0, -1);

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
            $index = 1;

            foreach ($html->find('#tenzi > p') as $element) {

                $verse_text = str_get_html($element->innertext);
                $verse_text = preg_replace('/^(\d+)./m', '<font color="#FF6F00">$1. </font>', $verse_text);
                $verse_text = str_replace("<em>", '<em><font color="#7e8acd">', $verse_text);
                $verse_text = str_replace("</em>", "</font></em>", $verse_text);
                $verse_text = SQLite3::escapeString($verse_text);

                if (!empty($verse_text)) {

                    $verse_no = $index;
                    $verse_id = $title_no . str_pad($verse_no, 3, "0", STR_PAD_LEFT);

                    $part = $part . "({$verse_id}, {$title_no}, {$verse_no}, '{$verse_text}'),";
                    $index++;
                }
            }
        }
    }

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
