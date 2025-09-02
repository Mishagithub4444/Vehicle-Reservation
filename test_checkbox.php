<!DOCTYPE html>
<html>
<head>
    <title>2FA Checkbox Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-group { margin: 15px 0; }
        label { display: block; margin: 5px 0; }
        input[type="text"], input[type="password"] { 
            padding: 8px; 
            width: 250px; 
            border: 1px solid #ddd; 
            border-radius: 4px; 
        }
        .checkbox-container {
            display: flex;
            align-items: center;
            margin: 15px 0;
            padding: 10px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            background: #f8fafc;
        }
        .checkbox-container input[type="checkbox"] {
            margin-right: 10px;
            transform: scale(1.2);
        }
        button { 
            padding: 10px 20px; 
            background: #007bff; 
            color: white; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
        }
        .result { 
            margin: 20px 0; 
            padding: 15px; 
            background: #f0f0f0; 
            border-radius: 4px; 
        }
    </style>
</head>
<body>
    <h2>2FA Checkbox Test</h2>
    
    <?php if ($_SERVER['REQUEST_METHOD'] == 'POST'): ?>
        <div class="result">
            <h3>Form Submission Results:</h3>
            <p><strong>All POST Data:</strong></p>
            <pre><?php print_r($_POST); ?></pre>
            
            <p><strong>2FA Checkbox Analysis:</strong></p>
            <?php if (isset($_POST['enable_2fa'])): ?>
                <p>✅ Checkbox was submitted</p>
                <p>Value: <?php echo htmlspecialchars($_POST['enable_2fa']); ?></p>
                <p>Evaluated as: <?php echo ($_POST['enable_2fa'] == '1') ? 'TRUE (2FA enabled)' : 'FALSE (2FA disabled)'; ?></p>
            <?php else: ?>
                <p>❌ Checkbox was NOT submitted (unchecked)</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" value="test_user" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" name="password" id="password" value="test123" required>
        </div>
        
        <div class="form-group">
            <div class="checkbox-container">
                <input type="checkbox" name="enable_2fa" id="enable_2fa" value="1">
                <label for="enable_2fa">
                    🔐 Sign In With 2FA (Enhanced Security)
                </label>
            </div>
        </div>
        
        <div class="form-group">
            <button type="submit">Test Submit</button>
        </div>
    </form>
    
    <script>
        // Add some JavaScript to test checkbox interaction
        document.getElementById('enable_2fa').addEventListener('change', function() {
            console.log('Checkbox changed:', this.checked, 'Value:', this.value);
            alert('Checkbox ' + (this.checked ? 'checked' : 'unchecked'));
        });
    </script>
</body>
</html>
