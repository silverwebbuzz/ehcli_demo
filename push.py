#!/usr/bin/env python3
import subprocess
import os

os.chdir('/Volumes/V-Data/Clinic/ehcli_demo')

try:
    # Set up HTTPS for Git
    subprocess.run(['git', 'config', 'credential.helper', 'osxkeychain'], check=True)
    
    # Try to push
    result = subprocess.run(
        ['git', 'push', '-u', 'origin', 'main'],
        capture_output=True,
        text=True,
        timeout=60,
        env={**os.environ, 'GIT_TERMINAL_PROMPT': '0'}
    )
    
    print("STDOUT:")
    print(result.stdout)
    print("\nSTDERR:")
    print(result.stderr)
    print(f"\nReturn code: {result.returncode}")
    
except Exception as e:
    print(f"Error: {e}")
