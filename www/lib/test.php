<?php

declare(strict_types=1);


/**
 * Exception which is thrown if test fails
 */
class TestCaseException extends Exception
{
}


/**
 * Base class for test cases
 */
abstract class TestSuite
{
    private ?string $expected_exception = null;
    private $expected_exception_code = null;

    /**
     * Checks if two values are equal
     * @param mixed $a  Value 1
     * @param mixed $b  Value 2
     */
    protected function expectEq($a, $b)
    {
        if ($a != $b) {
            throw new TestCaseException("EXPECT_EQ: '{$a}' != '{$b}'");
        }
    }

    /**
     * Expects exception to be thrown in test case
     * @param string  Exception class name
     */
    protected function expectException(string $exception_class)
    {
        $this->expected_exception = $exception_class;
    }

    /**
     * Expects exception with specified error code to be thrown in test case
     * @param string  Exception class name
     * @param mixed   Error code
     */
    protected function expectExceptionWithCode(string $exception_class, $code)
    {
        $this->expected_exception = $exception_class;
        $this->expected_exception_code = $code;
    }

    /**
     * Returns the expected exception
     * @return string  Class name of expected exception (or null)
     */
    public function getExpectedException(): ?string
    {
        return $this->expected_exception;
    }

    /**
     * Returns the expected exception code
     * @return mixed  Expected exception code (or null)
     */
    public function getExpectedExceptionCode()
    {
        return $this->expected_exception_code;
    }
}


/**
 * Runs test cases of given test suite
 * @param string $test_suite_class  Name of test suite class
 * @param array $test_names  List of tests to run. If empty, run all tests
 */
function run_tests(string $test_suite_class, array $test_names=[]): void
{
    // Get all test methods
    $methods = get_class_methods($test_suite_class);
    $tests = [];
    $max_length_test_name = 0;
    foreach ($methods as $method) {
        if (substr($method, 0, 4) == "test") {
            if (count($test_names) == 0 || in_array($method, $test_names)) {
                $tests[] = $method;
                if (strlen($test_suite_class) + strlen($method) + 2 > $max_length_test_name) {
                    $max_length_test_name = strlen($test_suite_class) + strlen($method) + 2;
                }
            }
        }
    }

    // Create new test case object for each test and run test
    foreach ($tests as $test) {
        $padded_test_name = str_pad("{$test_suite_class}::{$test}", $max_length_test_name);
        print("\033[36m{$padded_test_name}\033[0m ");
        try {
            ob_start();
            $start_time = microtime(true);
            $test_case_obj = new $test_suite_class();
            $expected_exception_thrown = false;
            $thrown_exception = null;
            try {
                $test_case_obj->$test();
            } catch (Exception $e) {
                // Check if expected exception has been thrown
                if ($test_case_obj->getExpectedException() === null
                    || get_class($e) != $test_case_obj->getExpectedException()) {
                    throw $e;
                } else {
                    $expected_exception_thrown = true;
                    $thrown_exception = $e;
                }
            }
            if ($test_case_obj->getExpectedException() !== null && !$expected_exception_thrown) {
                throw new TestCaseException(
                    "EXPECTED_EXCEPTION: Exception '{$test_case_obj->getExpectedException()}' not thrown"
                );
            }
            if ($test_case_obj->getExpectedException() !== null
                && $test_case_obj->getExpectedExceptionCode() != null
                && $test_case_obj->getExpectedExceptionCode() != $thrown_exception->getCode()) {
                throw new TestCaseException(
                    "EXPECTED_EXCEPTION_CODE: Thrown exception '{$test_case_obj->getExpectedException()}'"
                    . " has invalid code: '{$thrown_exception->getCode()}' != "
                    . "'{$test_case_obj->getExpectedExceptionCode()}'"
                );
            }
            $test_case_time_ms = number_format((microtime(true) - $start_time)/1000, 2);
            $output = ob_get_clean();
            print("\033[32m[OK]\033[0m {$test_case_time_ms} ms\n");
            if ($output) {
                print($output . "\n");
            }
        } catch (TestCaseException $e) {
            $output = ob_get_clean();
            print("\033[31m[FAILED]\033[0m\n");
            if ($output) {
                print($output . "\n");
            }
            print($e);
        } catch (Exception $e) {
            $output = ob_get_clean();
            print("\033[31m[FAILED]\033[0m\n");
            if ($output) {
                print($output . "\n");
            }
            print("Error during test:\n" . $e);
        }
        if (isset($test_case_obj)) {
            unset($test_case_obj);
        }
    }
}
