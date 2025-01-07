<?php

namespace App\Http\Traits;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Kreait\Firebase\Factory;

trait ManageFiles
{

    protected $bucket;

    public function __construct()
    {
        $firebase = (new Factory)
            ->withServiceAccount(storage_path(env('FIREBASE_CREDENTIALS')));
        $storage = $firebase->createStorage();
        $this->bucket = $storage->getBucket();
    }

    public function uploadFile($file, $directory, $is_audio = false)
    {
        // Validate file extension (audio or regular files)
        $file_exe = $is_audio ? 'mp3' : $file->extension();

        // Generate unique file name
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $fileName = $originalName . '_' . time() . '.' . $file_exe;

        // Store the file in the 'public' disk, which will save the file in 'storage/app/public'
        // $filePath = $file->storeAs($directory, $fileName, 'public');

        // Store the file in the 'public' disk
        $filePath = Storage::disk('public')->putFileAs($directory, $file, $fileName);
        return $filePath;
    }

    public function deleteFile($filePath)
    {
        // $file = public_path($filePath);
        // Attempt to delete the file from the 'public' disk
        $result = Storage::disk('public')->delete($filePath);
        //return $result;
    }

    public function uploadToFirebase($file, $directory, $is_audio = false)
    {
        // Initialize Firebase Storage
        $firebase = (new Factory)
            ->withServiceAccount(storage_path(env('FIREBASE_CREDENTIALS')));
        $storage = $firebase->createStorage();
        $bucket = $storage->getBucket();

        // Get file details
        $filePath = $file->getRealPath();
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $is_audio ? 'mp3' : $file->extension();

        // Generate unique filename
        $fileName = $directory . '/' . $originalName . '_' . time() . '.' . $extension;

        // Upload file to Firebase
        $this->bucket->upload(
            fopen($filePath, 'r'),
            ['name' => $fileName]
        );

        // Get signed URL valid for 1 year
        $fileObject = $this->bucket->object($fileName);
        $signedUrl = $fileObject->signedUrl(new \DateTime('1 year'));

        return $signedUrl;
    }

    public function deleteFromFirebase($filePath)
    {

        // Parse the URL to extract the path component
        $path = $this->extractFilePathFromUrl($filePath);
        $object = $this->bucket->object($path);
        if ($object->exists()) {
            $object->delete();
        }
    }

    function extractFilePathFromUrl($url)
    {
        // Parse the URL to extract the path component
        $parsedUrl = parse_url($url);

        // The path will be in the 'path' key of the parsed URL array
        $path = $parsedUrl['path'];

        // Remove the domain and bucket part (e.g., 'car-dealers-bffbe.appspot.com')
        // We'll split the path using '/' as the delimiter and ignore the first two elements (bucket name and file path)
        $pathParts = explode('/', $path);

        // Remove the first two elements (bucket and directory before file)
        array_shift($pathParts); // Remove the 'car-dealers-bffbe.appspot.com'
        array_shift($pathParts); // Remove the bucket name

        // Join the remaining parts to get the relative file path
        $filePath = implode('/', $pathParts);

        return $filePath;
    }
}
