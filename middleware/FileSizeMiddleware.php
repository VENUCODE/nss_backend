<?php
function fileSizeMiddleware($maxsize = 5 * 1024 * 1024) {
    return function ($request, $handler) use ($maxsize) {
        $uploadedFiles = $request->getUploadedFiles();
        if (empty($uploadedFiles)) {
            return $handler->handle($request);
        }
        $exceededSizeFiles = [];
        foreach ($uploadedFiles as $field => $file) {

            if (is_array($file)) {
                foreach ($file as $singleFile) {
                   
                    $size = $singleFile->getSize();
                    if ($size > $maxsize) {
                        $exceededSizeFiles[] = "{$singleFile->getClientFilename()} (size: " . formatBytes($size) . ")";
                    }
                }
            } else {
        
                $size = $file->getSize();
               
                if ($size > $maxsize) {
                    $exceededSizeFiles[] = "{$file->getClientFilename()} (size: " . formatBytes($size) . ")";
                }
            }
        }
        if (!empty($exceededSizeFiles)) {
            $errors = [];
            if (!empty($exceededSizeFiles)) {
                $errors[] = 'Files exceeded the maximum allowed size of ' . formatBytes($maxsize) . ': ' . implode(', ', $exceededSizeFiles);
            }
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(['message'=>"Max File size exceeded",'error' => $errors]));
            return $response->withStatus(422)->withHeader('Content-type',"application/json");
        }
        return $handler->handle($request);
    };
}

function formatBytes($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
