<?php
function UploadMiddleware($uploadDir)
{
    return function ($request, $handler) use ($uploadDir) {
        $uploadedFiles = $request->getUploadedFiles();
        $fileNames = [];
        $allowedExtensions = ['png', 'jpg', 'jpeg']; // Allowed file extensions
        $errorMessages = [];

        // Ensure the directory exists
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // First loop: Check file extensions and gather error messages
        if (isset($uploadedFiles['images'])) {
            $file = $uploadedFiles['images'];
            if (is_array($file)) {
                foreach ($file as $singleFile) {
                    // Check for errors and validate extension
                    if ($singleFile->getError() === 0) {
                        $extension = pathinfo($singleFile->getClientFilename(), PATHINFO_EXTENSION);
                        if (!in_array(strtolower($extension), $allowedExtensions)) {
                            $errorMessages[] = "File '{$singleFile->getClientFilename()}' has an invalid extension. Allowed types: " . implode(', ', $allowedExtensions);
                        }
                    } else {
                        $errorMessages[] = "Error uploading file '{$singleFile->getClientFilename()}': " . $singleFile->getError();
                    }
                }
            } else {
                // Handle single file upload
                if ($file->getError() === 0) {
                    $extension = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);
                    if (!in_array(strtolower($extension), $allowedExtensions)) {
                        $errorMessages[] = "File '{$file->getClientFilename()}' has an invalid extension. Allowed types: " . implode(', ', $allowedExtensions);
                    }
                } else {
                    $errorMessages[] = "Error uploading file '{$file->getClientFilename()}': " . $file->getError();
                }
            }
        }

        // If there are any error messages, send them back in the response
        if (!empty($errorMessages)) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(["message"=>"Invalid File extensions",'errors' => $errorMessages]));
            return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
        }
        if (isset($uploadedFiles['images'])) {
            $file = $uploadedFiles['images'];
            if (is_array($file)) {
                foreach ($file as $singleFile) {
                    if ($singleFile->getError() === 0) {
                        $filename = bin2hex(random_bytes(12)); 
                        $singleFile->moveTo($uploadDir . '/' . $filename); 
                        $fileNames[] = $filename; 
                    }
                }
            } else {
                
                if ($file->getError() === 0) {
                    $filename = bin2hex(random_bytes(10)); 
                    $file->moveTo($uploadDir . '/' . $filename); 
                    $fileNames[] = $filename; 
                }
            }
        }

        $parsedBody = $request->getParsedBody();
        $request = $request->withAttribute('fileNames', $fileNames)
                           ->withAttribute('parsedBody', $parsedBody);
        
        return $handler->handle($request);
    };
}
