<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use App\Services\ReportService;


class reportSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'A mini system of student report';

    protected $reportService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(ReportService $reportService)
    {
        parent::__construct();
        $this->reportService = $reportService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $this->info('Welcome to the mini student report system, Please enter the following: ');

        $student = $this->getStudent();
        $report  = $this->askValid('Report to generate (1 for Diagnostic, 2 for Progress, 3 for Feedback)', 'report choise', 'numeric | min:0 | max:3');

        $this->reportService->generateReport($student, $report);
    }

    public function getStudent()
    {
        $id       = $this->askValid('Student ID', 'id', 'min:8 | required');
        $students = $this->reportService->loadModelData('students');
        $student  = $students[array_search($id, array_column($students, 'id'))];

        if (empty($student)) {
            $this->error('There is no student record, please try again.');
            return $this->getStudent();
        }

        return $student;
    }

    public function getReport()
    {
        $id = $this->askValid('Student ID', 'id', 'min:8 | required');
        $students = $this->reportService->loadModelData('students');
        if (count($students) > 0) {
            $student = current(array_filter($students, function ($student) use ($id) {
                return $student['id'] == $id;
            }));
        }

        if (empty($student)) {
            $this->error('There is no student record, please try again.');
            return $this->getStudent();
        }

        return $student;
    }

    public function askValid($question, $field, $rules)
    {
        $value = $this->ask($question);

        if ($message = $this->reportService->validateInput($rules, $field, $value)) {
            $this->error($message);

            return $this->askValid($question, $field, $rules);
        }

        return $value;
    }
}
