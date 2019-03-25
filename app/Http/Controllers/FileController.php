<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\File;
use Input;

class FileController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $file = File::all();
        return response()->json(['status' => 'All files', 'data' => $file]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        // header('Access-Control-Allow-Origin: *');
        // $target_path = "uploads/";
        // $target_path = $target_path . basename($_FILES['file']['name']);
        // if (move_uploaded_file($_FILES['file']['tmp_name'], $target_path)) {
        //     header('Content-type: application/json');
        //     $data = ['success' => true, 'message' => 'Upload and move success'];
        //     echo json_encode($data);
        // } else {
        //     header('Content-type: application/json');
        //     $data = ['success' => false, 'message' => 'There was an error uploading the file, please try again!'];
        //     echo json_encode($data);
        // }

        if ($request->hasFile('file')) {
            $file = $request->file;
            $path = $request->file->store('files');
            $filename = $file->getClientOriginalName();

            $file = new File();
            $file->entry_id = $request->get('entry_id') || 0;
            $file->file = $path;
            $file->save();

            return response()->json(['status' => 'File uploaded successfully', 'data' => $file]);
        } else {
            // return 'select file';
            return response()->json(['status' => 'Select a file', 'data' => 0]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $file = File::where('_id', $id)->get();
        return response()->json(['status' => 'Single file', 'data' => $file]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $file = File::where('_id', $id)->delete();
        return response()->json(['status' => 'File deleted successfully', 'data' => $file]);
    }
}
