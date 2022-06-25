<?php

if (!isset($_GET["training_id"])) {
    header("Location:".HTTP_ROOT);
}
$training_id = intval($_GET["training_id"]);

try {
    $training = $_db->getTraining($training_id);
    $question = $_db->getQuestion($training_id);
} catch (DbException $e) {
    header("Location:".HTTP_ROOT);
}

$_title = $training["name"];

if ($question !== null) {
    $word_index = ($question["mode"] == "1->2") ? "word1_parsed" : "word2_parsed";
    $word_formatted = implode(", ", array_map(function ($x) {
        return implode("/", $x);
    }, $question[$word_index]["words"]));
}
?>
<script>
document.addEventListener('touchstart', handleTouchStart, false);
document.addEventListener('touchmove', handleTouchMove, false);
var xDown = null;
var yDown = null;
function getTouches(evt) {
    return evt.touches || evt.originalEvent.touches;
}
function handleTouchStart(evt) {
    const firstTouch = getTouches(evt)[0];
    xDown = firstTouch.clientX;
    yDown = firstTouch.clientY;
};
function handleTouchMove(evt) {
    if ( ! xDown || ! yDown ) {
        return;
    }
    var xUp = evt.touches[0].clientX;
    var yUp = evt.touches[0].clientY;
    var xDiff = xDown - xUp;
    var yDiff = yDown - yUp;
    if ( Math.abs( xDiff ) > Math.abs( yDiff ) ) {
        document.getElementById("question-form").submit();
    }
    xDown = null;
    yDown = null;
};
</script>
<fieldset class="question">
<?php if ($question === null): ?>
    <p class="question-word">All words learned!</p>
<?php else: ?>
<p class="question-infoA"><?=$training["name"]?></p>
<p class="question-infoB"><?=($question["mode"] == "1->2") ? $question["lang1"] . " &#10150; " . $question["lang2"] : $question["lang2"] . " &#10150; " . $question["lang1"] ?></p>
<p class="question-word"><?=$word_formatted?>
<?php if ($question[$word_index]["info"] !== null): ?>
    <span class="question-word-info">(<?=$question[$word_index]["info"]?>)</span>
<?php endif; ?>
</p>
<div class="question-field">
<form action="<?=HTTP_ROOT?>answer" method="get" id="question-form">
    <input type="hidden" name="training_id" value="<?=$training["id"]?>" />
    <input type="hidden" name="question_id" value="<?=$question["id"]?>" />
    <input type="text" name="answer" value="" autofocus autocomplete="off" autocapitalize="off" spellcheck="false" autocorrect="off" />
    <input type="submit" value="Ok" />
</form>
</div>
<p class="info">You have already given <?=$training["num_correct"]?> correct answers and <?=$training["num_wrong"]?> wrong answers.<br>
    There is a total of <?=$training["num_questions"]?> questions in this training including <?=$training["num_words"]?> different words.</p>
<?php endif; ?>
</fieldset>
