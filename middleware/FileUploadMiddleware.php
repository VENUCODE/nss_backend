<?php
function UploadMiddleware($uploadDir)
{
    return function ($request, $handler) use ($uploadDir) {
        // Save uploaded files
        $uploadedFiles = $request->getUploadedFiles();
        $fileNames = [];

        if (!is_array($uploadedFiles)) {
            $uploadedFiles = [$uploadedFiles];
        }

        foreach ($uploadedFiles as $field => $file) {
            if ($file->getError() == 0) {
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $filename = bin2hex(random_bytes(8));
                $file->moveTo($uploadDir . '/' . $filename);
                $fileNames[$field] = $filename;
            }
        }

        $parsedBody = $request->getParsedBody();
        $request = $request->withAttribute('fileNames', $fileNames)
                           ->withAttribute('parsedBody', $parsedBody);

        return $handler->handle($request);
    };
}