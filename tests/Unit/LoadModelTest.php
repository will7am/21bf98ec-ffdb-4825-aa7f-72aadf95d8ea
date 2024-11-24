<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\File;
use App\Services\ReportService;



class LoadModelTest extends TestCase
{
    protected $service;

    public function testLoadStudent()
    {
        $service = new ReportService;
        $studentSample = ['id' => 'student1', 'firstName' => 'Tony', 'lastName' => 'Stark'];
        $studentData = $service->loadModelData('students')[0];

        $this->assertNotEmpty($studentData);
        $this->assertEquals($studentSample['id'], $studentData['id']);
        $this->assertEquals($studentSample['firstName'], $studentData['firstName']);
        $this->assertEquals($studentSample['lastName'], $studentData['lastName']);
    }
}
