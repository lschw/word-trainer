<?php

$action = null;
$id = null;

$name = "";
$lang1 = "";
$lang2 = "";
$words = [];
$error = "";
$info = "";

// Determine action and list id
if (!isset($_GET["action"]) || !in_array($_GET["action"], ["new", "edit", "save", "delete", "export"])) {
    header("Location:".HTTP_ROOT);
}
$action = $_GET["action"];

if (isset($_GET["id"])) {
    $id = intval($_GET["id"]);
}

/**************************************
 * Action delete list
 **************************************/
if ($action == "delete") {
    if ($id !== null) {
        $_db->deleteList($id);
    }
    header("Location:".HTTP_ROOT);
}

/**************************************
 * Action export list
 **************************************/
if ($action == "export") {
    if ($id !== null) {
        try {
            $list = $_db->getList($id);
            $words = $_db->getWords($id);
            $csv = "#{$list["lang1"]};{$list["lang2"]}\n";
            foreach ($words as $word) {
                $csv .= "{$word["word1"]};{$word["word2"]}\n";
            }
            $filename = strtolower(
                preg_replace("/\_+/", "_", preg_replace("/[^a-zA-Z0-9_]/", "", str_replace(" ", "_", $list["name"])))
            ) . ".csv";
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . $filename);
            print($csv);
            exit(0);
        } catch (DbException $e) {
            header("Location:".HTTP_ROOT);
        }
    }
    exit(0);
}

/**************************************
 * Action edit list
 **************************************/
if ($action == "edit") {
    if ($id === null) {
        header("Location:".HTTP_ROOT);
    }
    try {
        $list = $_db->getList($id);
        $name = $list["name"];
        $lang1 = $list["lang1"];
        $lang2 = $list["lang2"];
        $words = $_db->getWords($id);
    } catch (DbException $e) {
        header("Location:".HTTP_ROOT);
    }
}

/**************************************
 * Action save list
 **************************************/
if ($action == "save") {
    if (!isset($_POST["name"]) || !isset($_POST["lang1"]) || !isset($_POST["lang2"])) {
        header("Location:".HTTP_ROOT);
    }
    $name = $_POST["name"];
    $lang1 = $_POST["lang1"];
    $lang2 = $_POST["lang2"];

    $new_words = [];
    $existing_words = [];
    foreach ($_POST as $key => $value) {
        if (substr($key, 0, 6) == "word1-") {
            $word_id = substr($key, 6);
            if (!array_key_exists("word2-".$word_id, $_POST)) {
                continue;
            }
            $word1 = trim($value);
            $word2 = trim($_POST["word2-".$word_id]);
            if (substr($word_id, 0, 3) == "new") {
                $new_words[] = ["id" => $word_id, "word1" => $word1, "word2" => $word2];
            } else {
                $word_id = intval($word_id);
                $existing_words[] = ["id" => $word_id, "word1" => $word1, "word2" => $word2];
            }
        }
    }
    try {
        if ($id !== null) {
            // Update existing list
            $_db->updateListWithWords(
                ["id"=> $id, "name"=> $name, "lang1"=> $lang1, "lang2" => $lang2],
                $existing_words,
                $new_words
            );
            $info = "List successfully updated";
        } else {
            // Create new list
            $id = $_db->addListWithWords(["name"=> $name, "lang1"=> $lang1, "lang2" => $lang2], $new_words);
            $info = "List successfully created";
        }
        $words = $_db->getWords($id);
    } catch (Exception $e) {
        $error = $e->getMessage();
        $words = array_merge($existing_words, $new_words);
    }
}


if ($id === null) {
    $_title = "New list";
} else {
    $_title = "Edit list '" . $name . "'";
}
?>
<script>
function on_import()
{
    var file = document.getElementById("import").files[0];

    var reader = new FileReader();
    reader.onload = (function(reader)
    {
        return function()
        {
            var contents = reader.result;
            var lines = contents.split('\n');
            var words = [];
            var delimiter = '';
            for(var i = 0; i < lines.length; i++) {
                line = lines[i].trim();
                if(line[0] == '#') {
                    // Skip comment lines
                    continue;
                }

                // Auto detect delimiter from first line
                if(delimiter == '') {
                    var delimiters = ['\t', ';', ','];
                    for(var j = 0; j < delimiters.length; j++) {
                        var cols = lines[i].split(delimiters[j]);
                        if(cols.length == 2) {
                            delimiter = delimiters[j];
                            break;
                        }
                    }
                    if(delimiter == '') {
                        continue;
                    }
                }
                var cols = lines[i].split(delimiter);
                if(cols.length == 2) {
                    words.push([cols[0].trim(), cols[1].trim()]);
                }
            }
            var tr_rows = document.getElementById("words_tbody").getElementsByTagName("tr");

            // Get last non-empty row
            var i_begin = 0;
            for (var i = tr_rows.length-1; i >= 0; i--) {
                var td_cols = tr_rows[i].getElementsByTagName("td");
                if(td_cols[1].firstChild.value != "" || td_cols[2].firstChild.value != "") {
                    i_begin = i+1;
                    break;
                }
            }
            // Ensure that there are enough empty rows
            append_rows(words.length - (tr_rows.length-i_begin));

            // Insert imported words
            for (var i = i_begin; i < i_begin+words.length; i++) {
                var td_cols = tr_rows[i].getElementsByTagName("td");
                td_cols[1].firstChild.value = words[i-i_begin][0];
                td_cols[2].firstChild.value = words[i-i_begin][1];
            }
        }
    })(reader);
    reader.readAsText(file);

}

function on_append_rows()
{
    append_rows(20);
}

function append_rows(num_rows)
{
    if(num_rows < 0) {
        return;
    }
    var tbody = document.getElementById("words_tbody");
    for (var i = 0; i < num_rows; i++) {
        var row = "<tr>";
        var row_num = tbody.children.length + 1;
        row += "<td class=\"align-right\">" + row_num + "</td>";
        row += "<td><input type=\"text\" value=\"\" name=\"word1-new" + row_num + "\"/></td>";
        row += "<td><input type=\"text\" value=\"\" name=\"word2-new" + row_num + "\"/></td>";
        row += "</tr>";
        tbody.insertAdjacentHTML("beforeend", row);
    }
}
</script>
<fieldset>
<legend><?=$_title?></legend>
<?php if ($info): ?>
    <div class="infobox"><?=$info?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="errorbox"><?=$error?></div>
<?php endif; ?>

<form action="<?=HTTP_ROOT?>list?action=save<?=($id !== null) ? "&id=".$id : ""?>" method="post">
<label for="name">Name</label>
<input type="text" value="<?=htmlentities($name)?>" name="name" placeholder="List name"/>

<br>
<label>Words</label>
<div class="info">
    <details>
    <summary>Syntax</summary>
    The characters "|/()" have special meaning
    <ul>
        <li><em>word</em> - Single meaning of word</li>
        <li><em>word|word-synonym1|word-synonym2</em> - Multiple synonyms of words are separated by "|"</li>
        <li><em>word_gender1/word_gender2</em> - Word in different grammatical genders are separated by "/"</li>
        <li><em>word (Some info about word)</em> - Additional info shown next word can be added in parentheses "()"</li>
    </ul>
    </details>
</div>
<table>
    <thead>
    <tr>
        <th class="align-right">#</th>
        <th><input type="text" value="<?=htmlentities($lang1)?>" name="lang1" placeholder="Language 1" /></th>
        <th><input type="text" value="<?=htmlentities($lang2)?>" name="lang2" placeholder="Language 2" /></th>
    </tr>
    </thead>
    <tbody id="words_tbody">
<?php
$num_words = 0;
foreach ($words as $word) {
    $num_words++; ?>
    <tr>
        <td class="align-right"><?=$num_words?></td>
        <td><input type="text" value="<?=htmlentities($word["word1"])?>" name="word1-<?=$word["id"]?>" /></td>
        <td><input type="text" value="<?=htmlentities($word["word2"])?>" name="word2-<?=$word["id"]?>" /></td>
    </tr>
<?php
} ?>
<?php for ($i = 0; $i < 20; $i++) {
        $num_words++
?>
    <tr>
        <td class="align-right"><?=$num_words?></td>
        <td><input type="text" value="" name="word1-new<?=$num_words?>" /></td>
        <td><input type="text" value="" name="word2-new<?=$num_words?>" /></td>
    </tr>
<?php
    } ?>
    </tbody>
</table>
<div class="align-right">
<label for="import" class="file-selection" title="Import words from two-column csv file">Import words</label>
<input type="file" id="import" onchange="on_import();" />
<input type="button" value="Add rows" onclick="on_append_rows();" title="Add new empty rows for entering words" />
<input type="submit" value="Save" title="Save word list" />
</div>
</form>
</fieldset>
