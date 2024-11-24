<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;



class ReportService
{

    public function validateInput($rules, $fieldName, $value)
    {
        $validator = Validator::make([
            $fieldName => $value
        ], [
            $fieldName => $rules
        ]);

        return $validator->fails()
            ? $validator->errors()->first($fieldName)
            : null;
    }


    public function loadModelData($modelName)
    {
        $path = public_path('data/' . $modelName . '.json');

        if (!file_exists($path)) {
            return response()->json(['error' => 'Record not found, please try again'], 404);
        }

        $data = json_decode(file_get_contents($path), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON format');
        }

        return $data;
    }

    public function formatResponseTree($targetStudentResponse, $questions)
    {
        foreach ($targetStudentResponse as &$singleResponse) {
            foreach ($singleResponse['responses'] as $key => $value) {
                $correctAnswer = current(array_filter($questions, function ($question) use ($value) {
                    return $value['questionId'] == $question['id'];
                }));
                $singleResponse['responses'][$key]['stem']   = $correctAnswer['stem'];
                $singleResponse['responses'][$key]['type']   = $correctAnswer['type'];
                $singleResponse['responses'][$key]['strand'] = $correctAnswer['strand'];
                $singleResponse['responses'][$key]['config'] = $correctAnswer['config'];
            }
        }

        return $targetStudentResponse;
    }

    public function convertDate($date)
    {
        return Carbon::createFromFormat('d/m/Y H:i:s', $date)->format('jS F Y');
    }

    public function errorResponse($errorMsg)
    {
        echo $errorMsg . PHP_EOL;
        return;
    }

    /** Supposed to be models for these, and then defines the object type for params */
    public function generateReport($student, $report)
    {

        $questions  = $this->loadModelData('questions');
        $assessment = current($this->loadModelData('assessments'));

        if (count($questions) > 0) {
            $strands = array_count_values(array_column($questions, 'strand'));
        }

        $studentResponses = $this->loadModelData('student-responses');
        if (empty($studentResponses)) {
            $this->errorResponse('There is no response records for this student, please try again.');
        }

        $targetStudentResponse = array_filter($studentResponses, function ($studentResponse) use ($student) {
            return (($studentResponse['student']['id'] == $student['id']) && !empty($studentResponse['completed']));
        });

        if (empty($targetStudentResponse)) {
            $this->errorResponse('There is no completed records for this student, not able to analyze.');
        }
        $targetStudentResponse = $this->formatResponseTree($targetStudentResponse, $questions);

        $output = [];
        $output['total_question'] = 0;
        $output['total_correct_answer'] = 0;

        /** Prepare statistic output */
        foreach ($strands as $key => $strand) {
            $output['total_question'] = $output['total_question'] + $strand;
            $output['stat'][$key]['name'] = $key;
            $output['stat'][$key]['total_question'] = $strand;
            $output['stat'][$key]['correct_answer'] = 0;
        }

        foreach (end($targetStudentResponse)['responses'] as $response) {

            if ($response['response'] == $response['config']['key']) {
                $output['stat'][$response['strand']]['correct_answer'] = $output['stat'][$response['strand']]['correct_answer'] + 1;
                $output['total_correct_answer'] = $output['total_correct_answer'] + 1;
            }
        }

        try {
            switch ($report) {
                case '1':

                    echo $student['firstName'] . ' ' . $student['lastName'] . ' recently completed Numeracy assessment on ' . $this->convertDate(end($targetStudentResponse)['completed']) . PHP_EOL;
                    echo 'He got ' . $output['total_correct_answer'] . ' questions right out of ' . $output['total_question'] . '. Details by strand given below:' . PHP_EOL;

                    foreach ($output['stat'] as $details) {
                        echo $details['name'] . ': ' . $details['correct_answer'] . ' out of ' . $details['total_question'] . PHP_EOL;
                    }

                    return true;

                case '2':

                    $oldest = 0;
                    $recent = 0;
                    $index  = 0;

                    echo $student['firstName'] . ' ' . $student['lastName'] . ' has completed ' . $assessment['name'] . ' assessment ' . count($targetStudentResponse) . ' times in total. Date and raw score given below: ' . PHP_EOL;

                    foreach ($targetStudentResponse as $singleResponse) {
                        if ($index == 0) {
                            $oldest = $singleResponse['results']['rawScore'];
                        }
                        if ($index == (count($targetStudentResponse) - 1)) {
                            $recent = $singleResponse['results']['rawScore'];
                        }
                        echo 'Date: ' . $this->convertDate($singleResponse['completed']) . ', Raw Score: ' . $singleResponse['results']['rawScore'] . ' out of ' . count($singleResponse['responses']) . PHP_EOL;
                        $index = $index + 1;
                    }

                    $correctDifference = $recent - $oldest;
                    $progress = ($correctDifference >= 0) ? 'more' : 'less';

                    echo $student['firstName'] . ' ' . $student['lastName'] . ' got ' . abs($correctDifference) . ' ' . $progress . ' correct in the recent assessment than the oldest.';
                    return true;

                case 3:

                    echo $student['firstName'] . ' ' . $student['lastName'] . ' recently completed Numeracy assessment on ' . $this->convertDate(end($targetStudentResponse)['completed']) . PHP_EOL;
                    echo 'He got ' . $output['total_correct_answer'] . ' questions right out of ' . $output['total_question'] . '. Details by strand given below:' . PHP_EOL;

                    $wrongAnsers = array_filter(end($targetStudentResponse)['responses'], function ($response) {
                        return $response['response'] != $response['config']['key'];
                    });

                    if (count($wrongAnsers) > 0) {
                        echo 'Feedback for wrong answers given below: ' . PHP_EOL;
                        echo '---------------------------------------------' . PHP_EOL;
                    } else {
                        echo 'He did a good job!';
                        break;
                    }
                    foreach ($wrongAnsers as $wrongAnswer) {
                        echo $wrongAnswer['stem'] . PHP_EOL;

                        $wrongChoise = current(array_filter($wrongAnswer['config']['options'], function ($option) use ($wrongAnswer) {
                            return $option['id'] == $wrongAnswer['response'];
                        }));
                        echo 'Your answer: ' . $wrongChoise['label'] . ' with ' . $wrongChoise['value'] . PHP_EOL;

                        $rightChoise = current(array_filter($wrongAnswer['config']['options'], function ($option) use ($wrongAnswer) {
                            return $option['id'] == $wrongAnswer['config']['key'];
                        }));
                        echo 'Right answer: ' . $rightChoise['label'] . ' with ' . $rightChoise['value'] . PHP_EOL;
                        echo 'Hint: ' . $wrongAnswer['config']['hint'] . PHP_EOL;
                        echo '---------------------------------------------' . PHP_EOL;
                    }
                    return true;

                default:
                    throw new \Exception('Invalid report type');
            }
            
        } catch (\Exception $e) {
            /** Usually I will put in the log table to inverstigate later */
            throw new \Exception('Generation erorr ' . $e);
        }
    }
}
