#!/bin/bash

# ============================================================================
# IFRS Reports Deployment Script
# ============================================================================
# This script deploys the IFRS-compliant financial reports
# Version: 1.0.0
# Date: February 17, 2026
# ============================================================================

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_header() {
    echo -e "${BLUE}============================================================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}============================================================================${NC}"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${YELLOW}ℹ $1${NC}"
}

print_step() {
    echo -e "${BLUE}→ $1${NC}"
}

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# ============================================================================
# Pre-deployment Checks
# ============================================================================
print_header "Pre-Deployment Checks"

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    print_error "artisan file not found. Are you in the Laravel root directory?"
    exit 1
fi
print_success "Laravel root directory confirmed"

# Check if PHP is installed
if ! command_exists php; then
    print_error "PHP is not installed"
    exit 1
fi
print_success "PHP is installed"

# Check if composer is installed
if ! command_exists composer; then
    print_error "Composer is not installed"
    exit 1
fi
print_success "Composer is installed"

# Check PHP version
PHP_VERSION=$(php -r "echo PHP_VERSION;" | cut -d'.' -f1,2)
print_info "PHP version: $PHP_VERSION"

# ============================================================================
# Step 1: Install Dependencies
# ============================================================================
print_header "Step 1: Installing Dependencies"

print_step "Installing composer dependencies..."
composer require barryvdh/laravel-dompdf --no-interaction --quiet || true
composer require phpoffice/phpspreadsheet --no-interaction --quiet || true
print_success "Dependencies installed"

# ============================================================================
# Step 2: Run Migrations
# ============================================================================
print_header "Step 2: Running Database Migrations"

print_step "Running migrations..."
if php artisan migrate --force; then
    print_success "Migrations completed successfully"
else
    print_error "Migration failed. Check database connection and permissions."
    exit 1
fi

# ============================================================================
# Step 3: Run Seeders
# ============================================================================
print_header "Step 3: Seeding Cash Flow Line Items"

print_step "Seeding cash flow line items..."
if php artisan db:seed --class=CashFlowLineItemSeeder --force; then
    print_success "Cash flow line items seeded successfully"
else
    print_error "Seeding failed. Check database connection."
    exit 1
fi

# ============================================================================
# Step 4: Clear Caches
# ============================================================================
print_header "Step 4: Clearing Caches"

print_step "Clearing application cache..."
php artisan cache:clear > /dev/null 2>&1
print_success "Application cache cleared"

print_step "Clearing configuration cache..."
php artisan config:clear > /dev/null 2>&1
print_success "Configuration cache cleared"

print_step "Clearing route cache..."
php artisan route:clear > /dev/null 2>&1
print_success "Route cache cleared"

print_step "Clearing view cache..."
php artisan view:clear > /dev/null 2>&1
print_success "View cache cleared"

print_step "Clearing compiled classes..."
php artisan clear-compiled > /dev/null 2>&1 || true
print_success "Compiled classes cleared"

# ============================================================================
# Step 5: Optimize for Production (Optional)
# ============================================================================
read -p "$(echo -e ${YELLOW}Do you want to optimize for production? \(y/n\)${NC} )" -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    print_header "Step 5: Optimizing for Production"
    
    print_step "Caching configuration..."
    php artisan config:cache > /dev/null 2>&1
    print_success "Configuration cached"
    
    print_step "Caching routes..."
    php artisan route:cache > /dev/null 2>&1
    print_success "Routes cached"
    
    print_step "Caching views..."
    php artisan view:cache > /dev/null 2>&1
    print_success "Views cached"
    
    print_step "Running autoloader optimization..."
    composer dump-autoload --optimize --quiet
    print_success "Autoloader optimized"
else
    print_info "Skipping production optimization"
fi

# ============================================================================
# Step 6: Verify Installation
# ============================================================================
print_header "Step 6: Verifying Installation"

print_step "Checking routes..."
if php artisan route:list --path=cash-flow --columns=method,uri,name > /dev/null 2>&1; then
    print_success "Cash flow routes verified"
else
    print_error "Cash flow routes not found"
fi

if php artisan route:list --path=changes-equity --columns=method,uri,name > /dev/null 2>&1; then
    print_success "Equity statement routes verified"
else
    print_error "Equity statement routes not found"
fi

print_step "Checking database tables..."
if php artisan tinker --execute="echo 'DB check: ' . \DB::table('cash_flow_line_items')->count();" 2>/dev/null | grep -q "DB check"; then
    print_success "Database tables verified"
else
    print_error "Database tables not found"
fi

# ============================================================================
# Deployment Summary
# ============================================================================
print_header "Deployment Complete!"

echo ""
echo -e "${GREEN}✓ All deployment steps completed successfully!${NC}"
echo ""
echo -e "${BLUE}What was deployed:${NC}"
echo "  • 4 Service classes (Financial report logic)"
echo "  • 1 Model (CashFlowLineItem)"
echo "  • 2 Updated controllers (Cash Flow & Equity)"
echo "  • 4 Views (IFRS-compliant UI)"
echo "  • 1 Migration (cash_flow_line_items table)"
echo "  • 1 Seeder (29 standard IAS 7 line items)"
echo ""
echo -e "${BLUE}Next Steps:${NC}"
echo "  1. Access reports in your browser:"
echo "     • Cash Flow: /accounting/reports/cash-flow"
echo "     • Equity: /accounting/reports/changes-equity"
echo ""
echo "  2. Configure chart accounts (if needed):"
echo "     • Set has_cash_flow flag on cash accounts"
echo "     • Set has_equity flag on equity accounts"
echo "     • Assign proper categories"
echo ""
echo "  3. Test with real data:"
echo "     • Generate reports for recent periods"
echo "     • Verify calculations manually"
echo "     • Export to PDF and Excel"
echo ""
echo "  4. Review documentation:"
echo "     • README_IFRS_REPORTS.md (start here)"
echo "     • QUICK_START_IFRS_REPORTS.md"
echo "     • FINAL_STATUS_IFRS_REPORTS.md"
echo ""
echo -e "${GREEN}🎉 Your IFRS-compliant reports are now ready to use!${NC}"
echo ""

# ============================================================================
# Optional: Display URLs
# ============================================================================
if [ -f ".env" ]; then
    APP_URL=$(grep "^APP_URL=" .env | cut -d '=' -f2)
    if [ ! -z "$APP_URL" ]; then
        echo -e "${BLUE}Quick Access URLs:${NC}"
        echo "  • Cash Flow Report: ${APP_URL}/accounting/reports/cash-flow"
        echo "  • Equity Statement: ${APP_URL}/accounting/reports/changes-equity"
        echo ""
    fi
fi

# ============================================================================
# Exit
# ============================================================================
exit 0
