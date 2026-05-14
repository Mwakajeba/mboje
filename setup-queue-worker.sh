#!/bin/bash

# Setup script for automatic queue worker
# This script helps you choose and set up the best option for your environment

echo "=========================================="
echo "Laravel Queue Worker Automatic Setup"
echo "=========================================="
echo ""
echo "Choose your setup option:"
echo "1. Supervisor (Recommended for production)"
echo "2. Systemd Service"
echo "3. Lower threshold (process more items synchronously - no worker needed)"
echo "4. Exit"
echo ""
read -p "Enter your choice (1-4): " choice

case $choice in
    1)
        echo ""
        echo "Setting up Supervisor..."
        echo ""
        
        # Check if supervisor is installed
        if ! command -v supervisorctl &> /dev/null; then
            echo "Supervisor is not installed. Installing..."
            sudo apt-get update
            sudo apt-get install -y supervisor
        fi
        
        # Get project path
        PROJECT_PATH=$(pwd)
        echo "Project path: $PROJECT_PATH"
        
        # Copy config
        sudo cp supervisor/laravel-worker.conf /etc/supervisor/conf.d/laravel-worker.conf
        
        # Update paths in config
        sudo sed -i "s|/home/anselim/smartaccounting|$PROJECT_PATH|g" /etc/supervisor/conf.d/laravel-worker.conf
        
        # Reload supervisor
        sudo supervisorctl reread
        sudo supervisorctl update
        sudo supervisorctl start laravel-worker:*
        
        echo ""
        echo "✅ Supervisor setup complete!"
        echo "Check status with: sudo supervisorctl status laravel-worker:*"
        ;;
        
    2)
        echo ""
        echo "Setting up Systemd Service..."
        echo ""
        
        # Get project path
        PROJECT_PATH=$(pwd)
        echo "Project path: $PROJECT_PATH"
        
        # Copy service file
        sudo cp systemd/laravel-worker.service /etc/systemd/system/laravel-worker.service
        
        # Update paths in service file
        sudo sed -i "s|/home/anselim/smartaccounting|$PROJECT_PATH|g" /etc/systemd/system/laravel-worker.service
        
        # Reload systemd
        sudo systemctl daemon-reload
        sudo systemctl enable laravel-worker
        sudo systemctl start laravel-worker
        
        echo ""
        echo "✅ Systemd service setup complete!"
        echo "Check status with: sudo systemctl status laravel-worker"
        ;;
        
    3)
        echo ""
        echo "Setting up lower threshold (no queue worker needed)..."
        echo ""
        
        read -p "Enter threshold (default 100, or 9999 to almost always process synchronously): " threshold
        threshold=${threshold:-100}
        
        # Add to .env
        if grep -q "PURCHASE_INVOICE_JOB_THRESHOLD" .env; then
            sed -i "s/PURCHASE_INVOICE_JOB_THRESHOLD=.*/PURCHASE_INVOICE_JOB_THRESHOLD=$threshold/" .env
        else
            echo "" >> .env
            echo "PURCHASE_INVOICE_JOB_THRESHOLD=$threshold" >> .env
        fi
        
        php artisan config:clear
        
        echo ""
        echo "✅ Threshold set to $threshold"
        echo "Invoices with less than $threshold items will process synchronously (no queue worker needed)"
        ;;
        
    4)
        echo "Exiting..."
        exit 0
        ;;
        
    *)
        echo "Invalid choice. Exiting..."
        exit 1
        ;;
esac

echo ""
echo "Setup complete! See QUEUE_WORKER_AUTOMATIC_SETUP.md for more details."

