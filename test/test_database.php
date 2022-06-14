<?php

declare(strict_types=1);

// Restrict execution of script to commandline
if (PHP_SAPI != "cli") {
    exit(1);
}

// Enable all errors
error_reporting(E_ALL);
ini_set("display_errors", "1");
ini_set("display_startup_errors", "1");
ini_set("html_errors", "1");

// Convert errors to exceptions
function my_error_handler($severity, $message, $filename, $lineno)
{
    throw new ErrorException($message, 0, $severity, $filename, $lineno);
}
set_error_handler("my_error_handler");

// Database settings
define("DB_PREFIX_TEST", "test81649163763_");

// Import libraries
define("PATH_ROOT", realpath(dirname(__FILE__)."/../")."/");
define("PATH_LIB", PATH_ROOT."lib/");
require_once(PATH_ROOT."config.php");
require_once(PATH_LIB."string_helper.php");
require_once(PATH_LIB."array_helper.php");
require_once(PATH_LIB."DbException.php");
require_once(PATH_LIB."Database.php");
require_once(PATH_LIB."test.php");

/**
 * Database test class
 */
class TestDatabase extends TestSuite
{
    private Database $db;

    public function __construct()
    {
        $this->db = new Database(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME, DB_PREFIX_TEST);
    }

    public function __destruct()
    {
        $this->db->clear();
    }

    public function testShouldCreateList()
    {
        $list1 = [
            "name" => "List 1",
            "lang1" => "en",
            "lang2" => "de"
        ];
        $id = $this->db->addList($list1["name"], $list1["lang1"], $list1["lang2"]);

        $list1_check = $this->db->getList($id);
        $this->expectEq($list1_check["name"], $list1["name"]);
        $this->expectEq($list1_check["lang1"], $list1["lang1"]);
        $this->expectEq($list1_check["lang2"], $list1["lang2"]);
    }

    public function testShouldCheckErrorWhenCreateTwoListsWithSameName()
    {
        $this->expectExceptionWithCode("DbException", DbException::DUPLICATE_LIST_NAME);
        $list1 = [
            "name" => "List 1",
            "lang1" => "en",
            "lang2" => "de"
        ];
        $id = $this->db->addList($list1["name"], $list1["lang1"], $list1["lang2"]);

        $list1_check = $this->db->getList($id);
        $this->expectEq($list1_check["name"], $list1["name"]);
        $this->expectEq($list1_check["lang1"], $list1["lang1"]);
        $this->expectEq($list1_check["lang2"], $list1["lang2"]);

        $id2 = $this->db->addList($list1["name"], $list1["lang1"], $list1["lang2"]);
    }

    public function testShouldCheckErrorWhenCreateListWithEmptyName()
    {
        $this->expectExceptionWithCode("DbException", DbException::EMPTY_LIST_NAME);
        $id = $this->db->addList("", "en", "de");
    }

    public function testShouldCheckErrorWhenCreateListWithEmptyLang1()
    {
        $this->expectExceptionWithCode("DbException", DbException::EMPTY_LIST_LANG1);
        $id = $this->db->addList("List 1", " ", "de");
    }

    public function testShouldCheckErrorWhenCreateListWithEmptyLang2()
    {
        $this->expectExceptionWithCode("DbException", DbException::EMPTY_LIST_LANG2);
        $id = $this->db->addList("List 1", "en", "");
    }

    public function testShouldCreateListWithTrimmedValues()
    {
        $list1 = [
            "name" => "List 1 ",
            "lang1" => " en",
            "lang2" => "de    "
        ];
        $id = $this->db->addList($list1["name"], $list1["lang1"], $list1["lang2"]);

        $list1_check = $this->db->getList($id);
        $this->expectEq($list1_check["name"], trim($list1["name"]));
        $this->expectEq($list1_check["lang1"], trim($list1["lang1"]));
        $this->expectEq($list1_check["lang2"], trim($list1["lang2"]));
    }

    public function testShouldCreateListWithWords()
    {
        $list = [
            "name" => "List 1",
            "lang1" => "en",
            "lang2" => "de"
        ];
        $words = [
            [
                "word1" => "desk",
                "word2" => "Tisch",
            ],
            [
                "word1" => "flower",
                "word2" => "Blume",
            ],
        ];

        $list_id = $this->db->addListWithWords($list, $words);

        $list_check = $this->db->getList($list_id);
        $this->expectEq($list_check["name"], trim($list["name"]));
        $this->expectEq($list_check["lang1"], trim($list["lang1"]));
        $this->expectEq($list_check["lang2"], trim($list["lang2"]));

        $words_check = $this->db->getWords($list_id);
        $this->expectEq(count($words_check), count($words));
        for ($i = 0; $i < count($words); $i++) {
            $this->expectEq($words_check[$i]["list"], $list_id);
            $this->expectEq($words_check[$i]["word1"], $words[$i]["word1"]);
            $this->expectEq($words_check[$i]["word2"], $words[$i]["word2"]);
        }
    }

    public function testShouldCheckErrorWhenCreateListWithInvalidWords()
    {
        $this->expectExceptionWithCode("DbException", DbException::MISSING_WORD_PROPERTY);

        $list = [
            "name" => "List 1",
            "lang1" => "en",
            "lang2" => "de"
        ];
        $words = [
            [
                "word1" => "desk",
            ],
            [
                "word1" => "flower",
                "word2" => "Blume",
            ],
        ];
        $list_id = $this->db->addListWithWords($list, $words);
    }

    public function testShouldUpdateList()
    {
        $list1 = [
            "name" => "List 1",
            "lang1" => "en",
            "lang2" => "de"
        ];
        $id = $this->db->addList($list1["name"], $list1["lang1"], $list1["lang2"]);

        $list1_check = $this->db->getList($id);
        $this->expectEq($list1_check["name"], $list1["name"]);
        $this->expectEq($list1_check["lang1"], $list1["lang1"]);
        $this->expectEq($list1_check["lang2"], $list1["lang2"]);

        $list2 = [
            "name" => "List Update",
            "lang1" => "en2",
            "lang2" => "de2"
        ];

        $this->db->updateList($id, $list2["name"], $list2["lang1"], $list2["lang2"]);

        $list2_check = $this->db->getList($id);
        $this->expectEq($list2_check["name"], $list2["name"]);
        $this->expectEq($list2_check["lang1"], $list2["lang1"]);
        $this->expectEq($list2_check["lang2"], $list2["lang2"]);
    }

    public function testShouldCheckErrorWhenUpdateListWithSameName()
    {
        $this->expectExceptionWithCode("DbException", DbException::DUPLICATE_LIST_NAME);

        $list1 = [
            "name" => "List 1",
            "lang1" => "en",
            "lang2" => "de"
        ];
        $list2 = [
            "name" => "List 2",
            "lang1" => "en",
            "lang2" => "de"
        ];
        $id1 = $this->db->addList($list1["name"], $list1["lang1"], $list1["lang2"]);
        $id2 = $this->db->addList($list2["name"], $list2["lang1"], $list2["lang2"]);

        $list1_check = $this->db->getList($id1);
        $list2_check = $this->db->getList($id2);
        $this->expectEq($list1_check["name"], $list1["name"]);
        $this->expectEq($list1_check["lang1"], $list1["lang1"]);
        $this->expectEq($list1_check["lang2"], $list1["lang2"]);
        $this->expectEq($list2_check["name"], $list2["name"]);
        $this->expectEq($list2_check["lang1"], $list2["lang1"]);
        $this->expectEq($list2_check["lang2"], $list2["lang2"]);

        // Try to update list 2 with name of list 1
        $this->db->updateList($id2, $list1["name"], $list2["lang1"], $list2["lang2"]);
    }

    public function testShouldCheckErrorWhenUpdateListWithEmptyLang1()
    {
        $this->expectExceptionWithCode("DbException", DbException::EMPTY_LIST_LANG1);

        $list1 = [
            "name" => "List 1",
            "lang1" => "en",
            "lang2" => "de"
        ];
        $id1 = $this->db->addList($list1["name"], $list1["lang1"], $list1["lang2"]);

        $list1_check = $this->db->getList($id1);
        $this->expectEq($list1_check["name"], $list1["name"]);
        $this->expectEq($list1_check["lang1"], $list1["lang1"]);
        $this->expectEq($list1_check["lang2"], $list1["lang2"]);

        // Try to update list with empty name
        $this->db->updateList($id1, $list1["name"], " ", $list1["lang2"]);
    }

    public function testShouldUpdateListWithWords()
    {
        $list = [
            "name" => "List 1",
            "lang1" => "en",
            "lang2" => "de"
        ];
        $words = [
            [
                "word1" => "desk",
                "word2" => "Tisch",
            ],
            [
                "word1" => "flower",
                "word2" => "Blume",
            ],
        ];

        $list_id = $this->db->addListWithWords($list, $words);

        $list_check = $this->db->getList($list_id);
        $this->expectEq($list_check["name"], trim($list["name"]));
        $this->expectEq($list_check["lang1"], trim($list["lang1"]));
        $this->expectEq($list_check["lang2"], trim($list["lang2"]));

        $words_check = $this->db->getWords($list_id);
        $this->expectEq(count($words_check), count($words));
        for ($i = 0; $i < count($words); $i++) {
            $this->expectEq($words_check[$i]["list"], $list_id);
            $this->expectEq($words_check[$i]["word1"], $words[$i]["word1"]);
            $this->expectEq($words_check[$i]["word2"], $words[$i]["word2"]);
        }

        $list_updated = [
            "id" => $list_id,
            "name" => "List 2",
            "lang1" => "en",
            "lang2" => "de"
        ];
        $words_updated = [];
        for ($i = 0; $i < count($words); $i++) {
            $words_updated[] = [
                "id" => $words_check[$i]["id"],
                "word1" => $words_check[$i]["word1"] . " - updated",
                "word2" => $words_check[$i]["word2"] . " - updated",
            ];
            // Set word to empty string for all but first word to check correct deletion
            if ($i > 0) {
                $words_updated[$i]["word1"] = "";
            }
        }

        $words_new = [
            [
                "word1" => "sun",
                "word2" => "Sonne",
            ],
            [
                "word1" => "car",
                "word2" => "Auto",
            ],
        ];

        $this->db->updateListWithWords($list_updated, $words_updated, $words_new);


        $list_check = $this->db->getList($list_id);
        $this->expectEq($list_check["name"], trim($list_updated["name"]));
        $this->expectEq($list_check["lang1"], trim($list_updated["lang1"]));
        $this->expectEq($list_check["lang2"], trim($list_updated["lang2"]));

        $words_check = $this->db->getWords($list_id);
        $words_updated = [$words_updated[0]]; // Keep only first word
        $words = array_merge($words_updated, $words_new);
        $this->expectEq(count($words_check), count($words));
        for ($i = 0; $i < count($words); $i++) {
            $this->expectEq($words_check[$i]["list"], $list_id);
            $this->expectEq($words_check[$i]["word1"], $words[$i]["word1"]);
            $this->expectEq($words_check[$i]["word2"], $words[$i]["word2"]);
        }
    }

    public function testShouldDeleteListAndWords()
    {
        $list = [
            "name" => "List 1",
            "lang1" => "en",
            "lang2" => "de"
        ];
        $words = [
            [
                "word1" => "desk",
                "word2" => "Tisch",
            ],
            [
                "word1" => "flower",
                "word2" => "Blume",
            ],
        ];

        $list_id = $this->db->addListWithWords($list, $words);

        $list_check = $this->db->getList($list_id);
        $this->expectEq($list_check["name"], trim($list["name"]));
        $this->expectEq($list_check["lang1"], trim($list["lang1"]));
        $this->expectEq($list_check["lang2"], trim($list["lang2"]));

        $words_check = $this->db->getWords($list_id);
        $this->expectEq(count($words_check), count($words));
        for ($i = 0; $i < count($words); $i++) {
            $this->expectEq($words_check[$i]["list"], $list_id);
            $this->expectEq($words_check[$i]["word1"], $words[$i]["word1"]);
            $this->expectEq($words_check[$i]["word2"], $words[$i]["word2"]);
        }

        $this->db->deleteList($list_id);

        $list_check = $this->db->getList($list_id, true);
        $this->expectEq($list_check, null);
        $words_check = $this->db->getWords($list_id);
        $this->expectEq(count($words_check), 0);
    }

    public function testShouldGetLists()
    {
        $list1 = [
            "name" => "XYZ",
            "lang1" => "en",
            "lang2" => "de"
        ];
        $list2 = [
            "name" => "ABC",
            "lang1" => "en",
            "lang2" => "de"
        ];
        $list1_id = $this->db->addList($list1["name"], $list1["lang1"], $list1["lang2"]);
        $list2_id = $this->db->addList($list2["name"], $list2["lang1"], $list2["lang2"]);

        $lists_check = $this->db->getLists();
        $this->expectEq(count($lists_check), 2);

        // Should be ordered by name -> expect reversed order
        $this->expectEq($lists_check[0]["id"], $list2_id);
        $this->expectEq($lists_check[0]["name"], $list2["name"]);
        $this->expectEq($lists_check[0]["lang1"], $list2["lang1"]);
        $this->expectEq($lists_check[0]["lang2"], $list2["lang2"]);
        $this->expectEq($lists_check[1]["id"], $list1_id);
        $this->expectEq($lists_check[1]["name"], $list1["name"]);
        $this->expectEq($lists_check[1]["lang1"], $list1["lang1"]);
        $this->expectEq($lists_check[1]["lang2"], $list1["lang2"]);
    }

    public function testShouldCreateTraining()
    {
        $list1 = [
            "name" => "List 1",
            "lang1" => "en",
            "lang2" => "de"
        ];
        $words1 = [
            [
                "word1" => "desk",
                "word2" => "Tisch",
            ],
            [
                "word1" => "flower",
                "word2" => "Blume",
            ],
        ];
        $list2 = [
            "name" => "List 2",
            "lang1" => "en",
            "lang2" => "de"
        ];
        $words2 = [
            [
                "word1" => "sun",
                "word2" => "Sonne",
            ],
            [
                "word1" => "car",
                "word2" => "Auto",
            ],
        ];

        $list1_id = $this->db->addListWithWords($list1, $words1);
        $list2_id = $this->db->addListWithWords($list2, $words2);

        $training1 = [
            "name" => "Training 1",
            "mode" => "1->2",
            "num_required_correct_answers" => 2,
            "correct_answers_consecutive" => true,
            "min_distance_same_question" => 10,
            "ignore_case" => true,
            "ignore_accent" => true,
            "ignore_punctuation_marks" => true,
            "ignore_article_lang1" => true,
            "ignore_article_lang2" => true,
            "require_only_one_meaning" => true,
        ];
        $training_id = $this->db->addTraining(
            $training1["name"],
            $training1["mode"],
            $training1["num_required_correct_answers"],
            $training1["correct_answers_consecutive"],
            $training1["min_distance_same_question"],
            $training1["ignore_case"],
            $training1["ignore_accent"],
            $training1["ignore_punctuation_marks"],
            $training1["ignore_article_lang1"],
            $training1["ignore_article_lang2"],
            $training1["require_only_one_meaning"],
            [$list1_id, $list2_id]
        );

        $training1_check = $this->db->getTraining($training_id);
        $this->expectEq($training1_check["id"], $training_id);
        $this->expectEq($training1_check["name"], $training1["name"]);
        $this->expectEq($training1_check["mode"], $training1["mode"]);
        $this->expectEq($training1_check["training_counter"], 0);
        $this->expectEq($training1_check["num_required_correct_answers"], $training1["num_required_correct_answers"]);
        $this->expectEq($training1_check["correct_answers_consecutive"], $training1["correct_answers_consecutive"]);
        $this->expectEq($training1_check["min_distance_same_question"], $training1["min_distance_same_question"]);
        $this->expectEq($training1_check["ignore_case"], $training1["ignore_case"]);
        $this->expectEq($training1_check["ignore_punctuation_marks"], $training1["ignore_punctuation_marks"]);
        $this->expectEq($training1_check["ignore_article_lang1"], $training1["ignore_article_lang1"]);
        $this->expectEq($training1_check["ignore_article_lang2"], $training1["ignore_article_lang2"]);
        $this->expectEq($training1_check["require_only_one_meaning"], $training1["require_only_one_meaning"]);
        $this->expectEq($training1_check["num_words"], count($words1)+count($words2));
        $this->expectEq(
            $training1_check["num_questions"],
            (count($words1)+count($words2))*$training1["num_required_correct_answers"]
        );
        $this->expectEq($training1_check["num_correct"], 0);
    }

    public function testShouldCheckErrorWhenCreateTwoTrainingsWithSameName()
    {
        $this->expectExceptionWithCode("DbException", DbException::DUPLICATE_TRAINING_NAME);

        $list1 = [
            "name" => "List 1",
            "lang1" => "en",
            "lang2" => "de"
        ];
        $list1_id = $this->db->addListWithWords($list1, []);
        $training1 = [
            "name" => "Training 1",
            "mode" => "1->2",
            "num_required_correct_answers" => 2,
            "correct_answers_consecutive" => true,
            "min_distance_same_question" => 10,
            "ignore_case" => true,
            "ignore_accent" => true,
            "ignore_punctuation_marks" => true,
            "ignore_article_lang1" => true,
            "ignore_article_lang2" => true,
            "require_only_one_meaning" => true,
        ];
        $training_id = $this->db->addTraining(
            $training1["name"],
            $training1["mode"],
            $training1["num_required_correct_answers"],
            $training1["correct_answers_consecutive"],
            $training1["min_distance_same_question"],
            $training1["ignore_case"],
            $training1["ignore_accent"],
            $training1["ignore_punctuation_marks"],
            $training1["ignore_article_lang1"],
            $training1["ignore_article_lang2"],
            $training1["require_only_one_meaning"],
            [$list1_id]
        );
        $training_id = $this->db->addTraining(
            $training1["name"],
            $training1["mode"],
            $training1["num_required_correct_answers"],
            $training1["correct_answers_consecutive"],
            $training1["min_distance_same_question"],
            $training1["ignore_case"],
            $training1["ignore_accent"],
            $training1["ignore_punctuation_marks"],
            $training1["ignore_article_lang1"],
            $training1["ignore_article_lang2"],
            $training1["require_only_one_meaning"],
            [$list1_id]
        );
    }

    public function testShouldCheckErrorWhenCreateTrainingWithInvalidList()
    {
        $this->expectExceptionWithCode("DbException", DbException::INVALID_LIST_ID);

        $training1 = [
            "name" => "Training 1",
            "mode" => "1->2",
            "num_required_correct_answers" => 2,
            "correct_answers_consecutive" => true,
            "min_distance_same_question" => 10,
            "ignore_case" => true,
            "ignore_accent" => true,
            "ignore_punctuation_marks" => true,
            "ignore_article_lang1" => true,
            "ignore_article_lang2" => true,
            "require_only_one_meaning" => true,
        ];
        $training_id = $this->db->addTraining(
            $training1["name"],
            $training1["mode"],
            $training1["num_required_correct_answers"],
            $training1["correct_answers_consecutive"],
            $training1["min_distance_same_question"],
            $training1["ignore_case"],
            $training1["ignore_accent"],
            $training1["ignore_punctuation_marks"],
            $training1["ignore_article_lang1"],
            $training1["ignore_article_lang2"],
            $training1["require_only_one_meaning"],
            [12345]
        );
    }


    public function testShouldCheckErrorWhenCreateTrainingWithInvalidMode()
    {
        $this->expectExceptionWithCode("DbException", DbException::INVALID_TRAINING_MODE);

        $list1 = [
            "name" => "List 1",
            "lang1" => "en",
            "lang2" => "de"
        ];
        $list1_id = $this->db->addListWithWords($list1, []);
        $training1 = [
            "name" => "Training 1",
            "mode" => "invalid-mode",
            "num_required_correct_answers" => 2,
            "correct_answers_consecutive" => true,
            "min_distance_same_question" => 10,
            "ignore_case" => true,
            "ignore_accent" => true,
            "ignore_punctuation_marks" => true,
            "ignore_article_lang1" => true,
            "ignore_article_lang2" => true,
            "require_only_one_meaning" => true,
        ];
        $training_id = $this->db->addTraining(
            $training1["name"],
            $training1["mode"],
            $training1["num_required_correct_answers"],
            $training1["correct_answers_consecutive"],
            $training1["min_distance_same_question"],
            $training1["ignore_case"],
            $training1["ignore_accent"],
            $training1["ignore_punctuation_marks"],
            $training1["ignore_article_lang1"],
            $training1["ignore_article_lang2"],
            $training1["require_only_one_meaning"],
            [$list1_id]
        );
    }

    public function testShouldCheckErrorWhenCreateTrainingWithEmptyName()
    {
        $this->expectExceptionWithCode("DbException", DbException::EMPTY_TRAINING_NAME);

        $list1 = [
            "name" => "List 1",
            "lang1" => "en",
            "lang2" => "de"
        ];
        $list1_id = $this->db->addListWithWords($list1, []);
        $training1 = [
            "name" => " ",
            "mode" => "1->2",
            "num_required_correct_answers" => 2,
            "correct_answers_consecutive" => true,
            "min_distance_same_question" => 10,
            "ignore_case" => true,
            "ignore_accent" => true,
            "ignore_punctuation_marks" => true,
            "ignore_article_lang1" => true,
            "ignore_article_lang2" => true,
            "require_only_one_meaning" => true,
        ];
        $training_id = $this->db->addTraining(
            $training1["name"],
            $training1["mode"],
            $training1["num_required_correct_answers"],
            $training1["correct_answers_consecutive"],
            $training1["min_distance_same_question"],
            $training1["ignore_case"],
            $training1["ignore_accent"],
            $training1["ignore_punctuation_marks"],
            $training1["ignore_article_lang1"],
            $training1["ignore_article_lang2"],
            $training1["require_only_one_meaning"],
            [$list1_id]
        );
    }

    public function testShouldGetTrainings()
    {
        $list1 = [
            "name" => "List 1",
            "lang1" => "en",
            "lang2" => "de"
        ];
        $list1_id = $this->db->addListWithWords($list1, []);
        $training1 = [
            "name" => "Training 1",
            "mode" => "1->2",
            "num_required_correct_answers" => 2,
            "correct_answers_consecutive" => true,
            "min_distance_same_question" => 10,
            "ignore_case" => true,
            "ignore_accent" => true,
            "ignore_punctuation_marks" => true,
            "ignore_article_lang1" => true,
            "ignore_article_lang2" => true,
            "require_only_one_meaning" => true,
        ];
        $training_id = $this->db->addTraining(
            $training1["name"],
            $training1["mode"],
            $training1["num_required_correct_answers"],
            $training1["correct_answers_consecutive"],
            $training1["min_distance_same_question"],
            $training1["ignore_case"],
            $training1["ignore_accent"],
            $training1["ignore_punctuation_marks"],
            $training1["ignore_article_lang1"],
            $training1["ignore_article_lang2"],
            $training1["require_only_one_meaning"],
            [$list1]
        );
        $training2 = [
            "name" => "Training 2",
            "mode" => "1->2",
            "num_required_correct_answers" => 2,
            "correct_answers_consecutive" => true,
            "min_distance_same_question" => 10,
            "ignore_case" => true,
            "ignore_accent" => true,
            "ignore_punctuation_marks" => true,
            "ignore_article_lang1" => true,
            "ignore_article_lang2" => true,
            "require_only_one_meaning" => true,
        ];
        $training_id = $this->db->addTraining(
            $training2["name"],
            $training2["mode"],
            $training2["num_required_correct_answers"],
            $training2["correct_answers_consecutive"],
            $training2["min_distance_same_question"],
            $training2["ignore_case"],
            $training2["ignore_accent"],
            $training2["ignore_punctuation_marks"],
            $training2["ignore_article_lang1"],
            $training2["ignore_article_lang2"],
            $training2["require_only_one_meaning"],
            [$list1_id]
        );

        $trainings_check = $this->db->getTrainings();
        $this->expectEq(count($trainings_check), 2);
    }

    public function testShouldCheckErrorWhenGetInvalidList()
    {
        $this->expectExceptionWithCode("DbException", DbException::INVALID_TRAINING_ID);
        $this->db->getTraining(1234);
    }

    public function testShouldDeleteTraining()
    {
        $list1 = [
            "name" => "List 1",
            "lang1" => "en",
            "lang2" => "de"
        ];
        $words1 = [
            [
                "word1" => "desk",
                "word2" => "Tisch",
            ],
            [
                "word1" => "flower",
                "word2" => "Blume",
            ],
        ];
        $list1_id = $this->db->addListWithWords($list1, $words1);
        $training1 = [
            "name" => "Training 1",
            "mode" => "1->2",
            "num_required_correct_answers" => 2,
            "correct_answers_consecutive" => true,
            "min_distance_same_question" => 10,
            "ignore_case" => true,
            "ignore_accent" => true,
            "ignore_punctuation_marks" => true,
            "ignore_article_lang1" => true,
            "ignore_article_lang2" => true,
            "require_only_one_meaning" => true,
        ];
        $training_id = $this->db->addTraining(
            $training1["name"],
            $training1["mode"],
            $training1["num_required_correct_answers"],
            $training1["correct_answers_consecutive"],
            $training1["min_distance_same_question"],
            $training1["ignore_case"],
            $training1["ignore_accent"],
            $training1["ignore_punctuation_marks"],
            $training1["ignore_article_lang1"],
            $training1["ignore_article_lang2"],
            $training1["require_only_one_meaning"],
            [$list1_id]
        );

        $training_check = $this->db->getTraining($training_id);
        $this->expectEq($training_check["id"], $training_id);

        $this->db->deleteTraining($training_id);

        try {
            $training_check = $this->db->getTraining($training_id);
        } catch (DbException $e) {
            $this->expectEq($e->getCode(), DbException::INVALID_TRAINING_ID);
        }
    }
}

run_tests("TestDatabase");
