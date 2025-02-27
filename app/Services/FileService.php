<?php

namespace App\Services;

class FileService
{
    public function updateFile($model, $request, $type)
    {
        if (!empty($model->file)) {
            $currentFile = public_path($model->file);

            if (file_exists($currentFile) && $currentFile !== public_path('/user-placeholder.png')) {
                unlink($currentFile);
            }
        }

        $uploadedFile = $request->file('file');
        $extension = strtolower($uploadedFile->getClientOriginalExtension());
        $name = time() . '.' . $extension;
        $destinationPath = public_path('file');
        $filePath = $destinationPath . '/' . $name;

        // Move the uploaded file
        $uploadedFile->move($destinationPath, $name);

        // Resize only if it's an image
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
            $this->resizeImage($filePath, 400, 400);
        }

        // Update model with new file path
        $model->file = '/file/' . $name;

        return $model;
    }

    private function resizeImage($filePath, $maxWidth, $maxHeight)
    {
        list($origWidth, $origHeight, $imageType) = getimagesize($filePath);

        // Create image resource from file
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($filePath);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($filePath);
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($filePath);
                break;
            default:
                return false;
        }

        // Maintain aspect ratio
        $aspectRatio = $origWidth / $origHeight;
        if ($origWidth > $origHeight) {
            $newWidth = $maxWidth;
            $newHeight = round($maxWidth / $aspectRatio);
        } else {
            $newHeight = $maxHeight;
            $newWidth = round($maxHeight * $aspectRatio);
        }

        // Create new resized image
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($resizedImage, $source, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

        // Save the resized image
        
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                imagejpeg($resizedImage, $filePath, 90);
                break;
            case IMAGETYPE_PNG:
                imagepng($resizedImage, $filePath);
                break;
            case IMAGETYPE_GIF:
                imagegif($resizedImage, $filePath);
                break;
        }

        imagedestroy($source);
        imagedestroy($resizedImage);
        return true;
    }
}