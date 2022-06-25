<?php

declare(strict_types=1);


class DbException extends Exception
{
    public const INVALID_LIST_ID = 1000;
    public const DUPLICATE_LIST_NAME = 1001;
    public const MISSING_LIST_PROPERTY = 1002;
    public const EMPTY_LIST_NAME = 1003;
    public const EMPTY_LIST_LANG1 = 1004;
    public const EMPTY_LIST_LANG2 = 1005;

    public const MISSING_WORD_PROPERTY = 2000;

    public const INVALID_TRAINING_ID = 3000;
    public const DUPLICATE_TRAINING_NAME = 3001;
    public const EMPTY_TRAINING_NAME = 3002;
    public const INVALID_TRAINING_MODE = 3003;
    public const NEGATIVE_NUM_REQUIRED_CORRECT_ANSWERS = 3004;

    public const INVALID_QUESTION_ID = 4000;
}
