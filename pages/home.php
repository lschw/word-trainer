<?php
$_title = "";
$lists = $_db->getLists();
$trainings = $_db->getTrainings();
?>
<script>
function change_word_list() {
    var list_ids = get_selected_options("lists");
    document.getElementById("button_list_edit").disabled = (list_ids.length != 1);
    document.getElementById("button_list_delete").disabled = (list_ids.length != 1);
    document.getElementById("button_list_export").disabled = (list_ids.length != 1);
    document.getElementById("button_training_new").disabled = (list_ids.length == 0);
}

function new_list() {
    location.href = "<?=HTTP_ROOT?>list?action=new";
}

function edit_list() {
    var list_ids = get_selected_options("lists");
    if(list_ids.length != 0) {
        location.href = "<?=HTTP_ROOT?>list?action=edit&id=" + list_ids[0];
    }
}

function delete_list() {
    var list_ids = get_selected_options("lists");
    var lists = {
<?php foreach ($lists as $list): ?>
        "<?=$list['id']?>" : "<?=$list['name']?>",
<?php endforeach; ?>
    };
    if(list_ids.length != 0) {
        if (confirm("Do you really want to delete list '" + lists[list_ids[0]] + "'?")) {
            location.href = "<?=HTTP_ROOT?>list?action=delete&id=" + list_ids[0];
        }
    }
}

function export_list() {
    var list_ids = get_selected_options("lists");
    if(list_ids.length != 0) {
        location.href = "<?=HTTP_ROOT?>list?action=export&id=" + list_ids[0];
    }
}

function change_training_list() {
    var training_ids = get_selected_options("trainings");
    document.getElementById("button_training_delete").disabled = (training_ids.length != 1);
    document.getElementById("button_training_start").disabled = (training_ids.length != 1);
}

function new_training() {
    var list_ids = get_selected_options("lists");
    if(list_ids.length == 0) {
        return;
    }
    var url_part = "&list_ids=";
    for(var i = 0; i < list_ids.length; i++) {
        url_part += list_ids[i];
        if(i+1 < list_ids.length) {
            url_part += "_";
        }
    }
    location.href = "<?=HTTP_ROOT?>training?action=new" + url_part;
}

function delete_training()
{
    var training_ids = get_selected_options("trainings");
    var trainings = {
<?php foreach ($trainings as $training): ?>
        "<?=$training['id']?>" : "<?=$training['name']?>",
<?php endforeach; ?>
    };
    if(training_ids.length != 0) {
        if (confirm("Do you really want to delete training '" + trainings[training_ids[0]] + "'?")) {
            location.href = "<?=HTTP_ROOT?>training?action=delete&id=" + training_ids[0];
        }
    }
}

function start_training() {
    var training_ids = get_selected_options("trainings");
    if(training_ids.length != 0) {
        location.href = "<?=HTTP_ROOT?>question?training_id=" + training_ids[0];
    }
}

function on_init() {
    change_word_list();
    change_training_list();
}

/**
 * Get all selected option values of <select> element
 * @param  id  Id of <select> element
 * @return Selected option values as array
 */
function get_selected_options(id)
{
    var select = document.getElementById(id);
    var result = [];
    for (var i = 0; i < select.options.length; i++) {
        var option = select.options[i];
        if (option.selected) {
            result.push(option.value);
        }
    }
    return result;
}
</script>

<h2>Word Lists</h2>
<select id="lists" multiple="multiple" onchange="change_word_list();">
<?php foreach ($lists as $list): ?>
    <option ondblclick="edit_list();" value="<?=$list["id"]?>">
        <?=$list["name"]?> (<?=$list["lang1"]?>/<?=$list["lang2"]?>), <?=$list["num_words"]?> words
    </option>
<?php endforeach; ?>
</select>
<input type="button" value="New" id="button_list_new" onclick="new_list();" title="Create new word list" />
<input type="button" value="Edit" id="button_list_edit" onclick="edit_list();" title="Edit selected word list" />
<input type="button" value="Delete" id="button_list_delete" onclick="delete_list();" title="Delete selected word list" />
<input type="button" value="Export" id="button_list_export" onclick="export_list();" title="Download selected word list as csv file" />
<input type="button" value="Train" id="button_training_new" onclick="new_training();" title="Create new training for (multiple) selected word lists" />

<h2>Trainings</h2>
<select id="trainings" multiple="multiple" onchange="change_training_list();">
<?php foreach ($trainings as $training): ?>
    <option ondblclick="start_training();" value="<?=$training["id"]?>">
        <?=$training["name"]?> (<?=$training["num_words"]?> words,
        <?=$training["num_correct"]?>/<?=$training["num_questions"]?>)
    </option>
<?php endforeach; ?>
<input type="button" value="Delete" id="button_training_delete" onclick="delete_training();" title="Delete selected training" />
<input type="button" value="Start" id="button_training_start" onclick="start_training();" title="Start selected training" />
</select>
<script>
window.onload = on_init();
</script>
