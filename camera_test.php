<?php
require_once 'config.php';

if (!is_logged_in()) {
    redirect('index.php');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Camera Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            padding: 20px;
        }
        .camera-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        #videoElement {
            width: 100%;
            max-width: 640px;
            height: auto;
            border-radius: 10px;
            background: #000;
            margin: 20px 0;
        }
        .btn-group {
            margin: 10px 0;
        }
        .status {
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .status.success {
            background: #d4edda;
            color: #155724;
        }
        .status.error {
            background: #f8d7da;
            color: #721c24;
        }
        .status.info {
            background: #d1ecf1;
            color: #0c5460;
        }
    </style>
</head>
<body>
    <div class="camera-container">
        <h2>📷 Camera Test Page</h2>
        <p class="text-muted">Test your camera functionality before using the attendance system</p>
        
        <div id="status" class="status info">
            Initializing camera...
        </div>
        
        <video id="videoElement" autoplay playsinline></video>
        
        <div class="btn-group">
            <button onclick="startCamera()" class="btn btn-primary">🎥 Start Camera</button>
            <button onclick="stopCamera()" class="btn btn-secondary">⏹️ Stop Camera</button>
            <button onclick="switchCamera()" class="btn btn-info">🔄 Switch Camera</button>
            <button onclick="testCapture()" class="btn btn-success">📸 Test Capture</button>
        </div>
        
        <div class="mt-4">
            <h5>Camera Information</h5>
            <div id="cameraInfo">
                <p><strong>Browser:</strong> <span id="browserInfo"></span></p>
                <p><strong>HTTPS:</strong> <span id="httpsInfo"></span></p>
                <p><strong>Camera Support:</strong> <span id="cameraSupport"></span></p>
                <p><strong>Current Camera:</strong> <span id="currentCamera"></span></p>
            </div>
        </div>
        
        <div class="mt-4">
            <h5>Troubleshooting</h5>
            <ul>
                <li>Make sure you're using HTTPS (required for camera access)</li>
                <li>Allow camera permissions when prompted</li>
                <li>Check if another app is using the camera</li>
                <li>Try refreshing the page</li>
                <li>Test with a different browser (Chrome/Firefox/Safari)</li>
            </ul>
        </div>
        
        <div class="mt-4">
            <a href="attendance_camera.php" class="btn btn-primary">📋 Go to Attendance Camera</a>
            <a href="dashboard.php" class="btn btn-secondary">🏠 Dashboard</a>
        </div>
    </div>

    <script>
        let stream = null;
        let currentFacingMode = 'user';
        
        // Update browser information
        document.getElementById('browserInfo').textContent = navigator.userAgent;
        document.getElementById('httpsInfo').textContent = location.protocol === 'https:' ? '✅ Yes' : '❌ No (Camera will not work)';
        document.getElementById('cameraSupport').textContent = !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia) ? '✅ Yes' : '❌ No';
        
        function updateStatus(message, type = 'info') {
            const status = document.getElementById('status');
            status.textContent = message;
            status.className = `status ${type}`;
        }
        
        async function startCamera() {
            updateStatus('Starting camera...', 'info');
            
            try {
                // Stop existing stream
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                }
                
                // Try different camera configurations
                const configs = [
                    { video: { facingMode: 'environment' }, audio: false },
                    { video: { facingMode: 'user' }, audio: false },
                    { video: true, audio: false }
                ];
                
                let cameraStarted = false;
                
                for (let i = 0; i < configs.length; i++) {
                    try {
                        stream = await navigator.mediaDevices.getUserMedia(configs[i]);
                        
                        if (stream.getVideoTracks().length > 0) {
                            const video = document.getElementById('videoElement');
                            video.srcObject = stream;
                            
                            const track = stream.getVideoTracks()[0];
                            const settings = track.getSettings();
                            currentFacingMode = settings.facingMode || 'unknown';
                            
                            document.getElementById('currentCamera').textContent = 
                                currentFacingMode === 'environment' ? 'Rear Camera' : 
                                currentFacingMode === 'user' ? 'Front Camera' : 'Unknown';
                            
                            updateStatus(`Camera started successfully! (${i === 0 ? 'Rear' : i === 1 ? 'Front' : 'Basic'} camera)`, 'success');
                            cameraStarted = true;
                            break;
                        }
                    } catch (error) {
                        console.log(`Config ${i + 1} failed:`, error);
                    }
                }
                
                if (!cameraStarted) {
                    throw new Error('No camera configuration worked');
                }
                
            } catch (error) {
                console.error('Camera error:', error);
                let errorMsg = 'Camera access failed: ';
                
                if (error.name === 'NotAllowedError') {
                    errorMsg += 'Permission denied. Please allow camera access.';
                } else if (error.name === 'NotFoundError') {
                    errorMsg += 'No camera found.';
                } else if (error.name === 'NotReadableError') {
                    errorMsg += 'Camera is already in use.';
                } else if (error.name === 'SecurityError') {
                    errorMsg += 'HTTPS required for camera access.';
                } else {
                    errorMsg += error.message;
                }
                
                updateStatus(errorMsg, 'error');
            }
        }
        
        function stopCamera() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
                document.getElementById('videoElement').srcObject = null;
                updateStatus('Camera stopped', 'info');
                document.getElementById('currentCamera').textContent = 'None';
            }
        }
        
        async function switchCamera() {
            if (!stream) {
                updateStatus('Please start camera first', 'error');
                return;
            }
            
            updateStatus('Switching camera...', 'info');
            
            try {
                // Stop current stream
                stream.getTracks().forEach(track => track.stop());
                
                // Switch facing mode
                const newFacingMode = currentFacingMode === 'user' ? 'environment' : 'user';
                
                stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: newFacingMode },
                    audio: false
                });
                
                const video = document.getElementById('videoElement');
                video.srcObject = stream;
                
                currentFacingMode = newFacingMode;
                document.getElementById('currentCamera').textContent = 
                    newFacingMode === 'environment' ? 'Rear Camera' : 'Front Camera';
                
                updateStatus('Camera switched successfully', 'success');
                
            } catch (error) {
                console.error('Switch error:', error);
                updateStatus('Failed to switch camera: ' + error.message, 'error');
                // Try to restart with original camera
                startCamera();
            }
        }
        
        function testCapture() {
            if (!stream) {
                updateStatus('Please start camera first', 'error');
                return;
            }
            
            const video = document.getElementById('videoElement');
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            
            ctx.drawImage(video, 0, 0);
            
            // Show the captured image
            const imageData = canvas.toDataURL('image/jpeg');
            const img = document.createElement('img');
            img.src = imageData;
            img.style.maxWidth = '200px';
            img.style.borderRadius = '10px';
            img.style.marginTop = '10px';
            
            // Remove previous test image if exists
            const prevImg = document.querySelector('.test-image');
            if (prevImg) prevImg.remove();
            
            img.className = 'test-image';
            video.parentNode.insertBefore(img, video.nextSibling);
            
            updateStatus('Photo captured successfully! Check below the video.', 'success');
        }
        
        // Auto-start camera on page load
        window.addEventListener('load', function() {
            setTimeout(startCamera, 1000);
        });
    </script>
</body>
</html>
