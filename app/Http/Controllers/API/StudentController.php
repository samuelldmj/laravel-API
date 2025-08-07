<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $students = Student::get();

        return response()->json([
            "status" => 'success',
            "data" => $students,
            'message' => 'Api works'
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:4',
            'email' => 'required|unique:students,email',
            'gender' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 400);
        }

        try {
            $student = Student::create($validator->validated());

            if (!$student) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to save student.'
                ], 500);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Saved Successfully',
                'data' => $student
            ], 201);


        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while saving the student.',
                'error' => $e->getMessage()
            ], 500);
        }



    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $student = Student::find($id);

        if (!$student) {
            return response()->json([
                'status' => 'error',
                'message' => 'This student does not exist'
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'data' => $student
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:4',
            'email' => 'required|unique:students,email,' . $id,
            'gender' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 400);
        }

        // Fetch student
        $student = Student::find($id);
        if (!$student) {
            return response()->json([
                'status' => 'error',
                'message' => 'This student does not exist'
            ], 404);
        }

        $student->update($validator->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Student data updated successfully',
            'data' => $student
        ], 200);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //fetch user
        $student = Student::find($id);

        //check if user exist
        if (!$student) {
            return response()->json([
                'status' => 'error',
                'message' => 'This student does not exist'
            ], 404);
        }

        //delete user
        $student->delete();

        //return a response to client
        return response()->json([
            'status' => 'success',
            'message' => 'Student deleted successfully'
        ], 204);

    }
}
