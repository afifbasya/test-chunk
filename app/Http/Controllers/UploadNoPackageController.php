<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadNoPackageController extends Controller
{
    public function upload(Request $request)
    {
        $file = $request->file('file');
        $chunkNumber = $request->input('chunkNumber');
        $totalChunks = $request->input('totalChunks');
        $fileName = $request->input('fileName');

        $tempPath = storage_path('app/temp');
        $chunkPath = $tempPath . '/' . $fileName;

        // Simpan chunk ke dalam direktori sementara
        $file->move($tempPath, $fileName . '.part' . $chunkNumber);

        // Jika semua chunk sudah diupload
        if ($chunkNumber == $totalChunks) {
            // Gabungkan semua chunk menjadi satu file
            $this->combineChunks($tempPath, $fileName, $totalChunks);

            // Simpan file ke dalam direktori yang sesuai
            Storage::disk('public')->put($fileName, file_get_contents($chunkPath));

            // Hapus semua chunk yang sudah diupload
            $this->deleteChunks($tempPath, $fileName, $totalChunks);
        }

        return response()->json(['message' => 'Chunk uploaded successfully']);
    }

    private function combineChunks($tempPath, $fileName, $totalChunks)
    {
        $combinedFile = fopen($tempPath . '/' . $fileName, 'wb');

        for ($i = 1; $i <= $totalChunks; $i++) {
            $chunk = file_get_contents($tempPath . '/' . $fileName . '.part' . $i);
            fwrite($combinedFile, $chunk);
        }

        fclose($combinedFile);
    }

    private function deleteChunks($tempPath, $fileName, $totalChunks)
    {
        for ($i = 1; $i <= $totalChunks; $i++) {
            unlink($tempPath . '/' . $fileName . '.part' . $i);
        }
    }
}
