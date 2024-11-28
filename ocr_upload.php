<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tesseract OCR Uploader</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #333; }
        .result { margin-top: 20px; padding: 10px; border: 1px solid #ccc; background: #f9f9f9; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>Tesseract OCR File Uploader</h1>
    <form action="ocr_upload.php" method="POST" enctype="multipart/form-data">
        <label for="image">Select an image (JPG, PNG):</label>
        <input type="file" name="image" id="image" accept=".jpg, .jpeg, .png" required>
        <button type="submit">Upload and Extract Text</button>
    </form>

    <?php
    require 'vendor/autoload.php'; // Include Composer autoloader

    use thiagoalessio\TesseractOCR\TesseractOCR;

    /**
     * Preprocess the image using Imagick
     *
     * @param string $inputFile Path to the input image file.
     * @param string $outputDir Directory to save preprocessed images.
     * @return string Path to the final preprocessed image.
     * @throws Exception If preprocessing fails.
     */
    function preprocessImage($inputFile, $outputDir)
    {
        try {
            $timestamp = date('Ymd_His'); // Get current date and time
            $imagick = new Imagick($inputFile);

            $imagick->modulateImage(100, 0, 100); // Step 1: Convert to grayscale
            $imagick->brightnessContrastImage(0, 10);  // Step 2: Adjust brightness and contrast

            // Final preprocessed file
            $preprocessedFile = $outputDir . 'preprocessed_' . $timestamp . '_' . basename($inputFile);
            $imagick->writeImage($preprocessedFile);

            return $preprocessedFile;
        } catch (Exception $e) {
            throw new Exception("Error during preprocessing: " . $e->getMessage());
        }
    }

    // Handle file upload and OCR
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
        $uploadDir = 'uploads/';
        $uploadedFile = $uploadDir . basename($_FILES['image']['name']);

        // Ensure uploads directory exists
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Move the uploaded file to the uploads directory
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadedFile)) {
            echo '<h3>Uploaded Image:</h3>';
            echo '<img src="' . htmlspecialchars($uploadedFile) . '" alt="Uploaded Image" style="max-width: 300px;">';

            try {
                // Preprocess the image
                $preprocessedFile = preprocessImage($uploadedFile, $uploadDir);

                // Perform OCR
                $ocr = new TesseractOCR($preprocessedFile);
                $ocr->lang('spa'); // Set the language to Spanish
                $text = $ocr->run();

                if (!is_null($text) && $text !== '') {
                    echo "<h3>Extracted Text:</h3><pre>" . htmlspecialchars($text) . "</pre>";
                } else {
                    echo "<h3>Extracted Text:</h3><p>No text was extracted from the image.</p>";
                }
            } catch (Exception $e) {
                echo "<p class='error'>Error performing OCR: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        } else {
            echo '<p class="error">Error uploading file. Please try again.</p>';
        }
    }
    ?>
</body>
</html>