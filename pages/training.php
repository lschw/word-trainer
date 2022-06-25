<?php
$_title = "New training";

$action = null;
$id = null;
$list_ids = null;

$name = date("Y-m-d");
$mode = "1<->2";
$num_required_correct_answers = "1";
$correct_answers_consecutive = true;
$min_distance_same_question = "20";
$ignore_case = true;
$ignore_accent = true;
$ignore_punctuation_marks = true;
$ignore_article_lang1 = false;
$ignore_article_lang2 = true;
$require_only_one_meaning = true;
$words = [];

$error = "";
$info = "";

// Determine action, training and list ids
if (!isset($_GET["action"]) || !in_array($_GET["action"], ["new", "save", "delete"])) {
    header("Location:".HTTP_ROOT);
}
$action = $_GET["action"];

if (isset($_GET["id"])) {
    $id = intval($_GET["id"]);
}

if (isset($_GET["list_ids"])) {
    $list_ids = array_values_to_int(explode("_", $_GET["list_ids"]));
}


/**************************************
 * Action delete training
 **************************************/
if ($action == "delete") {
    if ($id !== null) {
        $_db->deleteTraining($id);
    }
    header("Location:".HTTP_ROOT);
}

/**************************************
 * Action new training
 **************************************/
if ($action == "new" || $action == "save") {
    if ($list_ids === null) {
        header("Location:".HTTP_ROOT);
    }
    try {
        foreach ($list_ids as $id) {
            $words = array_merge($words, $_db->getWords($id));
            $list = $_db->getList($id);
            $name .= " / " . substr($list["name"], 0, 20);
        }
    } catch (DbException $e) {
        header("Location:".HTTP_ROOT);
    }
}

/**************************************
 * Action save training
 **************************************/
if ($action == "save") {
    if (!isset($_POST["name"])
        || !isset($_POST["mode"])
        || !isset($_POST["num_required_correct_answers"])
        || !isset($_POST["min_distance_same_question"])
    ) {
        header("Location:".HTTP_ROOT);
    }
    try {
        $bool_params = [
            "correct_answers_consecutive" => false,
            "ignore_case" => false,
            "ignore_accent" => false,
            "ignore_punctuation_marks" => false,
            "ignore_article_lang1" => false,
            "ignore_article_lang2" => false,
            "require_only_one_meaning" => false
        ];
        foreach ($bool_params as $bool_param=>$value) {
            $bool_params[$bool_param] = (isset($_POST[$bool_param]) && $_POST[$bool_param] == "yes") ? true : false;
        }

        $_db->addTraining(
            $_POST["name"],
            $_POST["mode"],
            intval($_POST["num_required_correct_answers"]),
            $bool_params["correct_answers_consecutive"],
            intval($_POST["min_distance_same_question"]),
            $bool_params["ignore_case"],
            $bool_params["ignore_accent"],
            $bool_params["ignore_punctuation_marks"],
            $bool_params["ignore_article_lang1"],
            $bool_params["ignore_article_lang2"],
            $bool_params["require_only_one_meaning"],
            $list_ids
        );
        header("Location:".HTTP_ROOT);
    } catch (DbException $e) {
        $error = $e->getMessage();
    }
}
?>
<fieldset>
<legend><?=$_title?></legend>
<?php if ($info): ?>
    <div class="infobox"><?=$info?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="errorbox"><?=$error?></div>
<?php endif; ?>

<form action="<?=HTTP_ROOT?>training?action=save&list_ids=<?=implode("_", $list_ids)?>" method="post">
<div class="align-right">
    <input type="submit" value="Save"  />
</div>
<label for="name">Name</label>
<input type="text" value="<?=htmlentities($name)?>" name="name" />
<br>

<label for="mode">Mode</label>
<select name="mode">
    <option value="1->2" <?=(($mode == "1->2") ? "selected" : "")?>>Language 1 &#10142; Language 2</option>
    <option value="2->1" <?=(($mode == "2->1") ? "selected" : "")?>>Language 2 &#10142; Language 1</option>
    <option value="1<->2" <?=(($mode == "1<->2") ? "selected" : "")?>>Language 1 &#11020; Language 2</option>
</select>
<span class="input-help-text">Specification of question mode. Show word in language 1 and ask for word in language 2, the other way around or ask both ways.</span>
<br>

<label for="num_required_correct_answers">Number of required correct answers</label>
<input type="number" value="<?=$num_required_correct_answers?>" name="num_required_correct_answers" min="1" max="100"/>
<span class="input-help-text">Each word is shown this many times and has to be answered correctly.</span>
<br>

<label for="correct_answers_consecutive">
    <input type="checkbox" name="correct_answers_consecutive" value="yes" <?=(($correct_answers_consecutive) ? "checked" : "")?> />
    Correct answers consecutive
</label>
<span class="input-help-text">If word has to be answered multiple times correctly (setting above), require that the answers must be consecutively correct with no wrong answers in between.</span>
<br>

<label for="min_distance_same_question">Minimum distance between same words being asked</label>
<input type="number" value="<?=$min_distance_same_question?>" name="min_distance_same_question" min="0" max="100"/>
<span class="input-help-text">Prevent words from being asked directly again. Ask at least this number of different questions until word is shown again.</span>
<br>

<label for="ignore_case">
    <input type="checkbox" name="ignore_case" value="yes" <?=(($ignore_case) ? "checked" : "")?> />
    Ignore case for evaluation
</label>
<span class="input-help-text">Words can be entered case insensitive.</span>
<br>

<label for="ignore_accent">
    <input type="checkbox" name="ignore_accent" value="yes" <?=(($ignore_accent) ? "checked" : "")?> />
    Ignore accents for evaluation
</label>
<span class="input-help-text">Words can be entered without accents, e.g. "e" instead of "é".</span>
<br>

<label for="ignore_punctuation_marks">
    <input type="checkbox" name="ignore_punctuation_marks" value="yes" <?=(($ignore_punctuation_marks) ? "checked" : "")?> />
    Ignore punctuation marks for evaluation
</label>
<span class="input-help-text">Sentences can be entered without punctuations marks "?!," ...</span>
<br>

<label for="ignore_article_lang1">
    <input type="checkbox" name="ignore_article_lang1" value="yes" <?=(($ignore_article_lang1) ? "checked" : "")?> />
    Ignore article in language 1 for evaluation
</label>
<span class="input-help-text">Words in language 1 can be entered without articles.</span>
<br>

<label for="ignore_article_lang2">
    <input type="checkbox" name="ignore_article_lang2" value="yes" <?=(($ignore_article_lang2) ? "checked" : "")?> />
    Ignore article in language 2 for evaluation
</label>
<span class="input-help-text">Words in language 2 can be entered without articles.</span>
<br>

<label for="require_only_one_meaning">
    <input type="checkbox" name="require_only_one_meaning" value="yes" <?=(($require_only_one_meaning) ? "checked" : "")?> />
    Require only one word meaning
</label>
<span class="input-help-text">If there are multiple meanings of a word, a single correct answer is enough.</span>
<br>

<div class="align-right">
<input type="submit" value="Save"  />
</div>
</form>
</fieldset>
<h2>Words in the training</h2>
<table>
    <thead>
    <tr>
        <th class="align-right">#</th>
        <th>Word 1</th>
        <th>Word 2</th>
    </tr>
    </thead>
    <tbody id="words_tbody">
<?php
$num_words = 0;
foreach ($words as $word) {
    $num_words++; ?>
    <tr>
        <td class="align-right"><?=$num_words?></td>
        <td><?=$word["word1"]?></td>
        <td><?=$word["word2"]?></td>
    </tr>
<?php
} ?>
    </tbody>
</table>
