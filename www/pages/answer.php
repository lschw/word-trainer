<?php
$_title = "";

if (!isset($_GET["answer"]) || !isset($_GET["question_id"]) || !isset($_GET["training_id"])) {
    header("Location:".HTTP_ROOT);
}

$answer = $_GET["answer"];
$question_id = intval($_GET["question_id"]);
$training_id = intval($_GET["training_id"]);

try {
    $training = $_db->getTraining($training_id);
    list($is_correct, $question) = $_db->evaluateQuestion($training_id, $question_id, $answer);
} catch (DbException $e) {
    header("Location:".HTTP_ROOT);
}

$word_index = ($question["mode"] == "1->2") ? "word1_parsed" : "word2_parsed";
$question_formatted = implode(", ", array_map(function ($x) {
    return implode("/", $x);
}, $question[$word_index]["words"]));

$word_index = ($question["mode"] == "1->2") ? "word2_parsed" : "word1_parsed";
$answer_correct_formatted = implode(", ", array_map(function ($x) {
    return implode("/", $x);
}, $question[$word_index]["words"]));


$_title = $training["name"];

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
    <p class="question-infoA"><?=$training["name"]?></p>
    <p class="question-infoB"><?=($question["mode"] == "1->2") ? $question["lang1"] . " &#10150; " . $question["lang2"] : $question["lang2"] . " &#10150; " . $question["lang1"] ?></p>
<?php if ($is_correct): ?>
    <p class="question-word correct">Correct!</p>
<?php else: ?>
    <p class="question-word wrong">Wrong!</p>
<?php endif; ?>
<p class="answer-word"><?=$question_formatted?> &nbsp;&nbsp;
&#10140;&nbsp;&nbsp; <?=$answer_correct_formatted?></p>
<p class="your-answer">Your answer: <span class="<?=(($is_correct) ? "correct" : "wrong")?>"><?=htmlentities($answer)?></span></p>

<form action="<?=HTTP_ROOT?>question" method="get" id="question-form">
    <input type="hidden" name="training_id" value="<?=$training_id?>" />
    <input type="submit" value="Continue" autofocus />
</form>
</fieldset>
