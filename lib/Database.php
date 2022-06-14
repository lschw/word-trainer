<?php

declare(strict_types=1);


/**
 * Database class to store and retrieve words
 */
class Database
{
    private string $db_server;
    private string $db_user;
    private string $db_password;
    private string $db_name;
    private string $db_prefix;
    private PDO $pdo;

    private string $table_words;
    private string $table_lists;
    private string $table_trainings;
    private string $table_training_words;


    /**
     * Connects to database
     * @param string $db_server  Database server name
     * @param string $db_user  Database user
     * @param string $db_password  Database user password
     * @param string $db_name  Database name
     * @param string $db_prefix  Database table prefix
     */
    public function __construct(
        string $db_server,
        string $db_user,
        string $db_password,
        string $db_name,
        string $db_prefix=""
    ) {
        $this->db_server = $db_server;
        $this->db_user = $db_user;
        $this->db_password = $db_password;
        $this->db_name = $db_name;
        $this->db_prefix = $db_prefix;
        $this->pdo = new PDO(
            "mysql:host=$this->db_server;dbname=$db_name",
            $db_user,
            $db_password
        );
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->table_words = "`" . $this->db_prefix . "words" . "`";
        $this->table_lists = "`" . $this->db_prefix . "lists" . "`";
        $this->table_trainings = "`" . $this->db_prefix . "trainings" . "`";
        $this->table_training_words = "`" . $this->db_prefix . "training_words" . "`";

        $this->setup();
    }


    /**
     * Creates all database tables
     */
    public function setup(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_words} (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            list INT UNSIGNED NOT NULL,
            word1 TEXT,
            word2 TEXT
        );
        CREATE TABLE IF NOT EXISTS {$this->table_lists} (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(200) NOT NULL,
            lang1 VARCHAR(200) NOT NULL,
            lang2 VARCHAR(200) NOT NULL
        );
        CREATE TABLE IF NOT EXISTS {$this->table_trainings} (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(200) NOT NULL,
            mode VARCHAR(200) NOT NULL,
            training_counter INT NOT NULL DEFAULT 0,
            num_required_correct_answers INT UNSIGNED NOT NULL,
            correct_answers_consecutive BOOLEAN NOT NULL,
            min_distance_same_question INT UNSIGNED NOT NULL,
            ignore_case BOOLEAN NOT NULL,
            ignore_accent BOOLEAN NOT NULL,
            ignore_punctuation_marks BOOLEAN NOT NULL,
            require_only_one_meaning BOOLEAN NOT NULL
        );
        CREATE TABLE IF NOT EXISTS {$this->table_training_words} (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            training INT UNSIGNED NOT NULL,
            word INT UNSIGNED NOT NULL,
            mode VARCHAR(200) NOT NULL,
            history TEXT DEFAULT '',
            correct BOOLEAN NOT NULL DEFAULT 0
        );";
        $this->pdo->exec($sql);
    }


    /**
     * Deletes all database tables
     */
    public function clear(): void
    {
        $this->pdo->exec("
            DROP TABLE IF EXISTS {$this->table_words};
            DROP TABLE IF EXISTS {$this->table_lists};
            DROP TABLE IF EXISTS {$this->table_trainings};
            DROP TABLE IF EXISTS {$this->table_training_words};
        ");
    }


    /**
     * Adds new list
     *
     * @param  string $name  Name of list
     * @param  string $lang1  First language of list
     * @param  string $lang2  Second language of list
     * @return int Id of new list
     */
    public function addList(string $name, string $lang1, string $lang2): int
    {
        $this->validateListData($name, $lang1, $lang2);

        $stmt = $this->pdo->prepare("
            SELECT
                COUNT(*)
            FROM
                {$this->table_lists}
            WHERE
                name=?
        ");
        $stmt->execute([$name]);
        if ($stmt->fetchColumn() != 0) {
            throw new DbException("List with name {$name} already exists", DbException::DUPLICATE_LIST_NAME);
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO {$this->table_lists}
                (name, lang1, lang2)
            VALUES
                (?, ?, ?)
        ");
        $stmt->execute([$name, $lang1, $lang2]);
        $list_id = intval($this->pdo->lastInsertId());
        return $list_id;
    }


    /**
     * Adds new list and its words
     *
     * @param  array $list  Associative array representing list
     * @param  array $words  Array of new words to add
     * @return int  Id of new list
     */
    public function addListWithWords(array $list, array $words): int
    {
        if (!array_keys_exist(["name", "lang1", "lang2"], $list)) {
            throw new DbException(
                "One or multiple keys of list are missing: " . implode(", ", array_keys($list)),
                DbException::MISSING_LIST_PROPERTY
            );
        }

        $this->beginTransaction();
        $list_id = $this->addList($list["name"], $list["lang1"], $list["lang2"]);
        $this->addWords($list_id, $words, false);
        $this->commitTransaction();
        return $list_id;
    }


    /**
     * Updates list
     *
     * @param int $id  Id of list to update
     * @param string $name  New name of list
     * @param string $lang1  First language of list
     * @param string $lang2  Second language of list
     */
    public function updateList(int $id, string $name, string $lang1, string $lang2): void
    {
        $stmt = $this->pdo->prepare("
            SELECT
                id
            FROM
                {$this->table_lists}
            WHERE
                name = ?
        ");
        $stmt->execute([$name]);
        $list_id = $stmt->fetchColumn();
        if ($list_id !== false && $id != $list_id) {
            throw new DbException("List with name {$name} already exists", DbException::DUPLICATE_LIST_NAME);
        }

        $this->validateListData($name, $lang1, $lang2);

        $stmt = $this->pdo->prepare(
            "
            UPDATE
                {$this->table_lists}
            SET
                name = ?, lang1 = ?, lang2 = ?
            WHERE
                id = ?"
        );
        $stmt->execute([$name, $lang1, $lang2, $id]);
    }


    /**
     * Updates list and its words
     * Existing words can be deleted by setting "word1" or "word2" to empty string in respective word in $words
     *
     * @param array $list  Associative array representing list
     * @param array $words  Array of existing words to update
     * @param array $words_new  Array of new words to add
     */
    public function updateListWithWords(array $list, array $words, array $words_new): void
    {
        if (!array_keys_exist(["id", "name", "lang1", "lang2"], $list)) {
            throw new DbException(
                "One or multiple keys of list are missing:" . implode(", ", array_keys($list)),
                DbException::MISSING_LIST_PROPERTY
            );
        }

        $list_check = $this->getList($list["id"]); // Ensure list exists

        $this->beginTransaction();
        $this->updateList($list["id"], $list["name"], $list["lang1"], $list["lang2"]);
        $this->updateWords($words, false);
        $this->addWords($list["id"], $words_new, false);
        $this->commitTransaction();
    }


    /**
     * Deletes list
     *
     * @param int $list_id  Delete list with this id
     */
    public function deleteList(int $list_id): void
    {
        $list = $this->getList($list_id); // Ensure list exists

        $this->beginTransaction();
        $this->deleteWords($list_id, false);

        $stmt = $this->pdo->prepare("
            DELETE FROM
                {$this->table_lists}
            WHERE
                id = ?
        ");
        $stmt->execute([$list_id]);

        $this->commitTransaction();
    }



    /**
     * Returns all lists
     *
     * @return array Array
     */
    public function getLists(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                id
            FROM
                {$this->table_lists}
            ORDER BY
                name
        ");
        $stmt->execute();
        $list_ids = array_values_to_int($stmt->fetchAll(PDO::FETCH_COLUMN));
        $lists = [];
        foreach ($list_ids as $list_id) {
            $lists[] = $this->getList($list_id);
        }
        return $lists;
    }


    /**
     * Returns list
     *
     * @param  int $list_id  Return list with this id
     * @param  bool $no_error  Do not throw exception if list does not exist but return null
     * @return array List as associative array
     */
    public function getList(int $list_id, bool $no_error=false): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                {$this->table_lists}.*,
                (
                    SELECT COUNT(*)
                    FROM {$this->table_words}
                    WHERE {$this->table_words}.list = {$this->table_lists}.id
                ) AS num_words
            FROM
                {$this->table_lists}
            WHERE
                {$this->table_lists}.id = ?
        ");
        $stmt->execute([$list_id]);
        $list = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($list === false) {
            if ($no_error) {
                return null;
            }
            throw new DbException("Retrieving list with id {$list_id} failed", DbException::INVALID_LIST_ID);
        }
        $list = array_values_to_int($list, ["id", "num_words"]);
        return $list;
    }


    /**
     * Adds words to list
     *
     * @param int $list_id  Add words to list with this id
     * @param array $words  Array of words
     * @param boolean $tflag  Whether to wrap database statements in transaction
     */
    public function addWords(int $list_id, array $words, bool $tflag=true): void
    {
        foreach ($words as $word) {
            if (!array_keys_exist(["word1", "word2"], $word)) {
                throw new DbException(
                    "One or multiple keys of word are missing: " . implode(", ", array_keys($word)),
                    DbException::MISSING_WORD_PROPERTY
                );
            }
        }

        $list = $this->getList($list_id); // Ensure list exists

        $this->beginTransaction($tflag);
        $stmt = $this->pdo->prepare("
            INSERT INTO
                {$this->table_words}
                    (list, word1, word2)
            VALUES
                (?, ?, ?)
        ");
        foreach ($words as $word) {
            if ($word["word1"] != "" && $word["word2"] != "") {
                $stmt->execute([$list_id, $word["word1"], $word["word2"]]);
            }
        }
        $this->commitTransaction($tflag);
    }


    /**
     * Update words
     *
     * @param array $words  Array of words
     * @param boolean $tflag  Whether to wrap database statements in transaction
     */
    public function updateWords(array $words, bool $tflag=true): void
    {
        foreach ($words as $word) {
            if (!array_keys_exist(["id", "word1", "word2"], $word)) {
                throw new DbException(
                    "One or multiple keys of word are missing: " . implode(", ", array_keys($word)),
                    DbException::MISSING_WORD_PROPERTY
                );
            }
        }

        $stmt1 = $this->pdo->prepare("
            UPDATE
                {$this->table_words}
            SET
                word1 = ?,
                word2 = ?
            WHERE
                id = ?
        ");
        $stmt2 = $this->pdo->prepare("
            DELETE FROM
                {$this->table_words}
            WHERE
                id = ?
        ");
        $stmt3 = $this->pdo->prepare("
            DELETE FROM
                {$this->table_training_words}
            WHERE
                word = ?
        ");

        $this->beginTransaction($tflag);
        foreach ($words as $word) {
            if ($word["word1"] != "" && $word["word2"] != "") {
                $stmt1->execute([$word["word1"], $word["word2"], $word["id"]]);
            } else {
                $stmt2->execute([$word["id"]]);
                $stmt3->execute([$word["id"]]);
            }
        }
        $this->commitTransaction($tflag);
    }


    /**
     * Returns all words of list
     *
     * @param  int $list_id  Return words of list with this id
     * @return array  Array of words, each word is represented by an associative array
     */
    public function getWords(int $list_id): array
    {
        $words = [];
        $stmt = $this->pdo->prepare("
            SELECT
                *
            FROM
                {$this->table_words}
            WHERE
                list = ?
        ");
        $stmt->execute([$list_id]);
        $words = $stmt->fetchAll(PDO::FETCH_ASSOC);
        for ($i = 0; $i < count($words); $i++) {
            $words[$i] = array_values_to_int($words[$i], ["id", "list"]);
        }
        return $words;
    }


    /**
     * Deletes all words of list
     *
     * @param  int $list_id  Delete words of list with this id
     * @param  bool $tflag  Whether to wrap database statements in transaction
     */
    public function deleteWords(int $list_id, bool $tflag=true): void
    {
        $this->beginTransaction($tflag);

        // Delete words from training
        $stmt = $this->pdo->prepare("
            DELETE FROM
                {$this->table_training_words}
            WHERE
                {$this->table_training_words}.word IN (
                    SELECT
                        {$this->table_words}.id
                    FROM
                        {$this->table_words}
                    WHERE
                        {$this->table_words}.list = ?
                )
        ");
        $stmt->execute([$list_id]);

        // Delete words
        $stmt = $this->pdo->prepare("
            DELETE FROM
                {$this->table_words}
            WHERE
                list = ?
        ");
        $stmt->execute([$list_id]);

        $this->commitTransaction($tflag);
    }


    /**
     * Adds new training
     *
     * @param string $name  Name of training, must be unique
     * @param string $mode  Training mode, must be either of
     *                      "1->2"  - Show word1, ask for word2
     *                      "2->1"  - Show word2, ask for word1
     *                      "1<->2" - Show word1, ask for word2 AND show word2, ask for word1
     * @param int $num_required_correct_answers  Specifies how many times a correct answer has to be given until a word
     *                                           is considered to be known and no longer shown in the training
     * @param bool $correct_answers_consecutive  Specifies whether correct answer must be consecutive. If true, the
     *                                           correct answer counter will be reset each time a wrong anser is given
     * @param int $min_distance_same_question  If possible, ask this number of different questions until the same
     *                                         question is shown again
     * @param bool $ignore_case  Ignore case sensitivity when comparing the answer with the correct answer
     * @param bool $ignore_accent  Ignore accents when comparing the answer with the correct answer
     * @param bool $ignore_punctuation_marks  Ignore punctuation marks when comparing the answer with the correct answer
     * @param bool $require_only_one_meaning  Require only a single correct meaning to be entered
     * @param array $list_ids  Use the words of these lists in the training
     * @return int Id of new created training
     */
    public function addTraining(
        string $name,
        string $mode,
        int $num_required_correct_answers,
        bool $correct_answers_consecutive,
        int $min_distance_same_question,
        bool $ignore_case,
        bool $ignore_accent,
        bool $ignore_punctuation_marks,
        bool $require_only_one_meaning,
        array $list_ids
    ): int {
        $stmt = $this->pdo->prepare("
            SELECT
                COUNT(*)
            FROM
                {$this->table_trainings}
            WHERE
                name=?
        ");
        $stmt->execute([$name]);
        if ($stmt->fetchColumn()) {
            throw new DbException("Training with name '{$name}' already exists", DbException::DUPLICATE_TRAINING_NAME);
        }

        $this->validateTrainingData(
            $name,
            $mode,
            $num_required_correct_answers,
            $correct_answers_consecutive,
            $min_distance_same_question,
            $ignore_case,
            $ignore_accent,
            $ignore_punctuation_marks,
            $require_only_one_meaning,
            $list_ids
        );

        $this->beginTransaction();

        $stmt = $this->pdo->prepare("
            INSERT INTO
                {$this->table_trainings}
                (
                    name, mode, num_required_correct_answers, correct_answers_consecutive, min_distance_same_question,
                    ignore_case, ignore_accent, ignore_punctuation_marks, require_only_one_meaning
                )
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $mode, $num_required_correct_answers, intval($correct_answers_consecutive),
            $min_distance_same_question, intval($ignore_case), intval($ignore_accent),
            intval($ignore_punctuation_marks), intval($require_only_one_meaning)
        ]);
        $training_id = intval($this->pdo->lastInsertId());

        $stmt = $this->pdo->prepare("
            INSERT INTO
                {$this->table_training_words}
                (
                    training, word, mode
                )
            VALUES
                (?, ?, ?)
        ");

        foreach ($list_ids as $list_id) {
            $words = $this->getWords($list_id);

            $list_check = $this->getList($list_id); // Ensure list exists
            foreach ($words as $word) {
                if ($mode == "1->2" || $mode == "2->1") {
                    $stmt->execute([$training_id, $word["id"], $mode]);
                }
                if ($mode == "1<->2") {
                    $stmt->execute([$training_id, $word["id"], "1->2"]);
                    $stmt->execute([$training_id, $word["id"], "2->1"]);
                }
            }
        }
        $this->commitTransaction();
        return $training_id;
    }

    /**
     * Returns all trainings
     *
     * @return array  Array of all trainings. Each array element represents one training as associative array
     */
    public function getTrainings(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                id
            FROM
                {$this->table_trainings}
            ORDER BY
                name
        ");
        $stmt->execute();
        $training_ids = array_values_to_int($stmt->fetchAll(PDO::FETCH_COLUMN));
        $trainings = [];
        foreach ($training_ids as $training_id) {
            $trainings[] = $this->getTraining($training_id);
        }
        return $trainings;
    }


    /**
     * Returns training with given id
     *
     * @param  int $training_id  Training id
     * @return array Associative array representing training
     */
    public function getTraining(int $training_id): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                {$this->table_trainings}.*,
                (
                    SELECT COUNT(DISTINCT word)
                    FROM {$this->table_training_words}
                    WHERE {$this->table_training_words}.training = {$this->table_trainings}.id
                ) AS num_words,
                (
                    SELECT COUNT(*) * {$this->table_trainings}.num_required_correct_answers
                    FROM {$this->table_training_words}
                    WHERE {$this->table_training_words}.training = {$this->table_trainings}.id
                ) AS num_questions,
                (
                    SELECT SUM(correct)
                    FROM {$this->table_training_words}
                    WHERE {$this->table_training_words}.training = {$this->table_trainings}.id
                ) AS num_correct
            FROM
                {$this->table_trainings}
            WHERE {$this->table_trainings}.id = ?
        ");
        $stmt->execute([$training_id]);
        $training = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($training === false) {
            throw new DbException(
                "Retrieving training with id {$training_id} failed",
                DbException::INVALID_TRAINING_ID
            );
        }
        $training = array_values_to_int($training, ["id", "training_counter", "num_required_correct_answers",
            "correct_answers_consecutive", "min_distance_same_question", "num_words", "num_questions",
            "num_correct"]);
        $training = array_values_to_bool(
            $training,
            ["ignore_case", "ignore_accent", "ignore_punctuation_marks", "require_only_one_meaning"]
        );
        $training["num_wrong"] = $training["training_counter"] - $training["num_correct"];
        return $training;
    }


    /**
     * Deletes training
     *
     * @param int $training_id  Delete training with this id
     */
    public function deleteTraining(int $training_id): void
    {
        $this->beginTransaction();

        // Delete words
        $stmt = $this->pdo->prepare("
            DELETE FROM
                {$this->table_training_words}
            WHERE
                training = ?
        ");
        $stmt->execute([$training_id]);

        // Delete training
        $stmt = $this->pdo->prepare("
            DELETE FROM
                {$this->table_trainings}
            WHERE
                id = ?
        ");
        $stmt->execute([$training_id]);

        $this->commitTransaction();
    }


    /**
     * Returns new word question
     *
     * @param  int $training_id  Return question for training with this id
     * @return array,null  Question as array or null if there are no more questions
     */
    public function getQuestion(int $training_id): ?array
    {
        $training = $this->getTraining($training_id);
        $stmt = $this->pdo->prepare("
            SELECT
                {$this->table_training_words}.*,
                {$this->table_words}.word1,
                {$this->table_words}.word2,
                {$this->table_lists}.lang1,
                {$this->table_lists}.lang2
            FROM
                {$this->table_training_words}
            INNER JOIN
                {$this->table_words}
            ON
                {$this->table_words}.id = {$this->table_training_words}.word
            INNER JOIN
                {$this->table_lists}
            ON
                {$this->table_lists}.id = {$this->table_words}.list
            WHERE
                {$this->table_training_words}.training = ?
        ");
        $stmt->execute([$training_id]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $history = [];
        for ($i = 0; $i < count($questions); $i++) {
            $questions[$i]["history"] = array_values_to_int(array_filter(explode(",", $questions[$i]["history"])));
            $questions[$i]["word1_parsed"] = $this->parseWord($questions[$i]["word1"]);
            $questions[$i]["word2_parsed"] = $this->parseWord($questions[$i]["word2"]);

            foreach ($questions[$i]["history"] as $qh) {
                $history[$qh] = $questions[$i];
            }
        }
        ksort($history);

        shuffle($questions);

        // First pass
        // - Skip all finished questions
        // - Skip all not yet asked questions
        // - Skip all questions which were shown within minimum question distance (consider also inverse questions)
        foreach ($questions as $question) {
            if ($question["correct"] >= $training["num_required_correct_answers"]) {
                continue;
            }
            if (count($question["history"]) == 0) {
                continue;
            }
            if (end($question["history"]) + $training["min_distance_same_question"] > $training["training_counter"]) {
                continue;
            }

            $history_cnt = 0;
            $found = false;
            foreach (array_reverse($history) as $question2) {
                if ($history_cnt == $training["min_distance_same_question"]) {
                    break;
                }
                if ($question2["word"] == $question["word"]) {
                    $found = true;
                    break;
                }
                $history_cnt++;
            }
            if ($found) {
                continue;
            }
            return $question;
        }

        // Second pass
        // - Skip all finished questions
        // - Skip all already asked questions
        // - Skip all new questions where inverse was already shown within minimum question distance
        foreach ($questions as $question) {
            if ($question["correct"] >= $training["num_required_correct_answers"]) {
                continue;
            }
            if (count($question["history"]) > 0) {
                continue;
            }

            $history_cnt = 0;
            $found = false;
            foreach (array_reverse($history) as $question2) {
                if ($history_cnt == $training["min_distance_same_question"]) {
                    break;
                }
                if ($question2["word"] == $question["word"]) {
                    $found = true;
                    break;
                }
                $history_cnt++;
            }
            if ($found) {
                continue;
            }
            return $question;
        }

        // Third pass
        // - Skip all finished questions
        foreach ($questions as $question) {
            if ($question["correct"] >= $training["num_required_correct_answers"]) {
                continue;
            }
            return $question;
        }
        return null;
    }


    /**
     * Evaluates answer to word question
     *
     * @param  int $training_id  Id of training
     * @param  int $question_id  Id of question
     * @param  string $answer  Answer to question
     * @return array [$is_correct, $question]
     *               bool $is_correct  Whether answer was correct or not
     *               array $question  Question data
     */
    public function evaluateQuestion(int $training_id, int $question_id, string $answer): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                {$this->table_training_words}.*,
                {$this->table_words}.word1,
                {$this->table_words}.word2,
                {$this->table_lists}.lang1,
                {$this->table_lists}.lang2
            FROM
                {$this->table_training_words}
            INNER JOIN
                {$this->table_words}
            ON
                {$this->table_words}.id = {$this->table_training_words}.word
            INNER JOIN
                {$this->table_lists}
            ON
                {$this->table_lists}.id = {$this->table_words}.list
            WHERE {$this->table_training_words}.id = ?
        ");
        $stmt->execute([$question_id]);
        $question = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($question === false) {
            throw new DbException(
                "Retrieving question with id '{$question_id}' failed",
                DbException::INVALID_QUESTION_ID
            );
        }
        $question["history"] = array_values_to_int(array_filter(explode(",", $question["history"])));
        $question["word1_parsed"] = $this->parseWord($question["word1"]);
        $question["word2_parsed"] = $this->parseWord($question["word2"]);

        $training = $this->getTraining($training_id);

        $answer_given_parsed = explode(",", $answer);
        $answer_given_parsed[] = trim($answer); // Add answer also as a whole to handle answers containing commas
        $answer_given_parsed = array_filter(array_map("trim", $answer_given_parsed));
        $answer_correct_parsed = $question["word2_parsed"]["words"];
        if ($question["mode"] == "2->1") {
            $answer_correct_parsed = $question["word1_parsed"]["words"];
        }

        // Apply answer formatting for comparison
        if ($training["ignore_case"]) {
            $answer_given_parsed = array_map("strtolower", $answer_given_parsed);
            for ($i = 0; $i < count($answer_correct_parsed); $i++) {
                $answer_correct_parsed[$i] = array_map("strtolower", $answer_correct_parsed[$i]);
            }
        }
        if ($training["ignore_accent"]) {
            $answer_given_parsed = array_map("convert_accented_chars_to_ascii", $answer_given_parsed);
            for ($i = 0; $i < count($answer_correct_parsed); $i++) {
                $answer_correct_parsed[$i] = array_map("convert_accented_chars_to_ascii", $answer_correct_parsed[$i]);
            }
        }
        if ($training["ignore_punctuation_marks"]) {
            $answer_given_parsed = array_map("remove_punctuation_chars", $answer_given_parsed);
            for ($i = 0; $i < count($answer_correct_parsed); $i++) {
                $answer_correct_parsed[$i] = array_map("remove_punctuation_chars", $answer_correct_parsed[$i]);
            }
        }

        // Check for correct answer
        $num_words_correct = 0;
        foreach ($answer_correct_parsed as $answer_correct) {
            $found = true;
            foreach ($answer_correct as $answer_correct_gender) {
                if (!in_array($answer_correct_gender, $answer_given_parsed)) {
                    $found = false;
                    break;
                }
            }
            if ($found) {
                $num_words_correct++;
            }
        }

        $is_correct = false;
        if ($num_words_correct == count($answer_correct_parsed)
            || ($num_words_correct > 0 && $training["require_only_one_meaning"])) {
            $is_correct = true;
        }

        // Ensure that correct answers are consecutive.
        // I.e. in order to get `num_required_correct_answers` correct answers per questions
        // there must not be a wrong answer in between
        if ($training["correct_answers_consecutive"] and $question["correct"] > 0 and !$is_correct) {
            $question["correct"] = 0; // Reset correct counter
        }
        $question["correct"] += ($is_correct) ? 1 : 0;
        $question["history"][] = $training["training_counter"]+1;

        $this->beginTransaction();
        $stmt = $this->pdo->prepare("
            UPDATE
                {$this->table_training_words}
            SET
                history = ?,
                correct = ?
            WHERE
                id = ?
        ");
        $stmt->execute(
            [
                implode(",", array_values_to_string($question["history"])),
                $question["correct"],
                $question_id
            ]
        );

        $stmt = $this->pdo->prepare("
            UPDATE
                {$this->table_trainings}
            SET
                training_counter = training_counter + 1
            WHERE
                id = ?
        ");
        $stmt->execute([$training_id]);

        $this->commitTransaction();
        return [$is_correct, $question];
    }


    /**
     * Transaction wrapper to allow nested transactions. Only begin transaction when given flag is true
     *
     * @param bool $tflag  Whether to actually begin new transaction
     */
    private function beginTransaction(bool $tflag=true): void
    {
        if ($tflag) {
            $this->pdo->beginTransaction();
        }
    }


    /**
     * Transaction wrapper to allow nested transactions. Only commit transaction when given flag is true
     *
     * @param  bool $tflag  Whether to actually commit transaction or not
     */
    private function commitTransaction(bool $tflag=true): void
    {
        if ($tflag) {
            $this->pdo->commit();
        }
    }


    /**
     * Validates list data
     *
     * @param string $name  @see addList()
     * @param string $lang1  @see addList()
     * @param string $lang2  @see addList()
     */
    private function validateListData(string &$name, string &$lang1, string &$lang2): void
    {
        $name = trim($name);
        $lang1 = trim($lang1);
        $lang2 = trim($lang2);

        if ($name == "") {
            throw new DbException("Name must not be empty", DbException::EMPTY_LIST_NAME);
        }
        if ($lang1 == "") {
            throw new DbException("Language 1 must not be empty", DbException::EMPTY_LIST_LANG1);
        }
        if ($lang2 == "") {
            throw new DbException("Language2 must not be empty", DbException::EMPTY_LIST_LANG2);
        }
    }


    /**
     * Validates training data
     *
     * @param  string $name  @see addTraining()
     * @param  string $mode  @see addTraining()
     * @param  int $num_required_correct_answers  @see addTraining()
     * @param  bool $correct_answers_consecutive  @see addTraining()
     * @param  int $min_distance_same_question  @see addTraining()
     * @param  bool $ignore_case  @see addTraining()
     * @param  bool $ignore_accent  @see addTraining()
     * @param  bool $ignore_punctuation_marks  @see addTraining()
     * @param  bool $require_only_one_meaning  @see addTraining()
     * @param  array $list_ids  @see addTraining()
     */
    private function validateTrainingData(
        string &$name,
        string &$mode,
        int &$num_required_correct_answers,
        bool &$correct_answers_consecutive,
        int &$min_distance_same_question,
        bool &$ignore_case,
        bool &$ignore_accent,
        bool &$ignore_punctuation_marks,
        bool &$require_only_one_meaning,
        array &$list_ids
    ): void {
        $name = trim($name);
        if ($name == "") {
            throw new DbException("Name must not be empty", DbException::EMPTY_TRAINING_NAME);
        }
        if (!in_array($mode, ["1->2", "2->1", "1<->2"])) {
            throw new DbException("Invalid mode: '{$mode}'", DbException::INVALID_TRAINING_MODE);
        }
        if ($num_required_correct_answers < 0) {
            throw new DbException(
                "Number of correct answers must be positive",
                DbException::NEGATIVE_NUM_REQUIRED_CORRECT_ANSWERS
            );
        }
        $list_ids = array_values_to_int($list_ids);
    }

    /**
     * Parse database word entry
     * @param  string $word  Entry to parse
     * @return array  Parsed word as array with keys ("words", "info"),
     *                where "words" is an array containing potential different grammatical genders
     */
    private function parseWord(string $word): array
    {
        // Check for info text
        $info = null;
        preg_match("/\((.*)\)/", $word, $match_info);
        if (count($match_info) != 0) {
            $info = trim($match_info[1]);
            $word = preg_replace("/\((.*)\)/", "", $word);
        }

        // Check for multiple meanings
        $words = [];
        $meanings = explode("|", $word);
        foreach ($meanings as $meaning) {

            // Check for multiple grammatical genders
            $meaning_grammatical_genders = explode("/", $meaning);
            $meaning_grammatical_genders = array_map("trim", $meaning_grammatical_genders);
            $meaning_grammatical_genders = array_filter($meaning_grammatical_genders);
            $words[] = $meaning_grammatical_genders;
        }
        return ["words" => $words, "info" => $info];
    }
}
