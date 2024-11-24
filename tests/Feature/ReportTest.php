<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Services\ReportService;


class ReportTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    protected $reportService;
    public function testGenerateDiagnosticReport()
    {
        $service = new ReportService;
        $student = ['id' => 'student1', 'firstName' => 'Tony', 'lastName' => 'Stark'];
        $report = 1;
        $this->assertTrue($service->generateReport($student, $report));

    }

    public function testGenerateProgressReport()
    {
        $service = new ReportService;
        $student = ['id' => 'student1', 'firstName' => 'Tony', 'lastName' => 'Stark'];
        $report = 2;
        $this->assertTrue($service->generateReport($student, $report));

    }
    
    public function testGenerateFeedbackReport()
    {
        $service = new ReportService;
        $student = ['id' => 'student1', 'firstName' => 'Tony', 'lastName' => 'Stark'];
        $report = 3;
        $this->assertTrue($service->generateReport($student, $report));

    }
}
