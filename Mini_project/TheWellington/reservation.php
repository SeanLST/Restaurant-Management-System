<?php 
session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: account.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Wellington | Reservation</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
    <style>
        .reservation-container-new {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            padding: 0 30px;
        }
        .card-new {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .card-new:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.12);
        }
        .card-title-new {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            font-weight: 600;
            color: #2d2d2d;
            margin-bottom: 25px;
            text-align: center;
            letter-spacing: 0.5px;
        }
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 0 10px;
        }
        .calendar-month {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            font-weight: 600;
            color: #8b6f47;
        }
        .calendar-nav-btn {
            background: none;
            border: none;
            color: #8b6f47;
            font-size: 20px;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: all 0.3s;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .calendar-nav-btn:hover {
            background: rgba(139, 111, 71, 0.1);
        }
        .calendar-weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
            margin-bottom: 10px;
        }
        .calendar-weekday {
            text-align: center;
            font-size: 13px;
            font-weight: 600;
            color: #8b6f47;
            padding: 10px 0;
            letter-spacing: 0.5px;
        }
        .calendar-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
        }
        .calendar-day {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            background: #fafafa;
            color: #5d5d5d;
        }
        .calendar-day:hover:not(.unavailable) {
            background: rgba(139, 111, 71, 0.1);
            transform: scale(1.05);
        }
        .calendar-day.available {
            background: #f0ede6;
            color: #3d3d3d;
        }
        .calendar-day.selected {
            background: #8b6f47;
            color: white;
            box-shadow: 0 4px 15px rgba(139, 111, 71, 0.3);
        }
        .calendar-day.unavailable {
            background: #f5f5f5;
            color: #d0d0d0;
            cursor: not-allowed;
        }
        .time-slots {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }
        .time-slot {
            padding: 14px;
            border: 2px solid #e8e4dc;
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            color: #5d5d5d;
            transition: all 0.3s;
            background: white;
        }
        .time-slot:hover:not(.unavailable) {
            border-color: #8b6f47;
            background: rgba(139, 111, 71, 0.05);
            transform: translateY(-2px);
        }
        .time-slot.selected {
            background: #8b6f47;
            color: white;
            border-color: #8b6f47;
        }
        .time-slot.unavailable {
            background: #f9f9f9;
            color: #d0d0d0;
            cursor: not-allowed;
            border-color: #f0f0f0;
        }
        .party-size-control {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 30px;
            padding: 30px 0;
        }
        .party-btn {
            width: 45px;
            height: 45px;
            border: 2px solid #e8e4dc;
            border-radius: 50%;
            background: white;
            color: #8b6f47;
            font-size: 20px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .party-btn:hover {
            border-color: #8b6f47;
            background: rgba(139, 111, 71, 0.05);
            transform: scale(1.1);
        }
        .guest-count-display {
            text-align: center;
        }
        .guest-number {
            font-family: 'Playfair Display', serif;
            font-size: 48px;
            font-weight: 700;
            color: #8b6f47;
            line-height: 1;
        }
        .guest-label {
            font-size: 16px;
            color: #8d8d8d;
            margin-top: 5px;
            font-style: italic;
        }
        .form-group-new {
            margin-bottom: 20px;
        }
        .form-label-new {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #5d5d5d;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }
        .form-input-new, .form-textarea-new {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #e8e4dc;
            border-radius: 12px;
            font-size: 15px;
            font-family: 'Cormorant Garamond', serif;
            transition: all 0.3s;
            background: white;
            color: #3d3d3d;
        }
        .form-input-new:focus, .form-textarea-new:focus {
            outline: none;
            border-color: #8b6f47;
            box-shadow: 0 0 0 3px rgba(139, 111, 71, 0.1);
        }
        .form-textarea-new {
            resize: vertical;
            min-height: 100px;
        }
        .table-floor-plan {
            background: linear-gradient(135deg, #f5f1ea 0%, #faf8f5 100%);
            border-radius: 16px;
            padding: 40px 30px;
            position: relative;
            min-height: 300px;
        }
        .table-node {
            position: absolute;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border: 3px solid white;
        }
        .table-node.available {
            background: #a8c8a8;
            color: white;
        }
        .table-node.occupied {
            background: #d4a574;
            color: white;
            cursor: not-allowed;
        }
        .table-node.selected {
            background: #8b6f47;
            color: white;
            transform: scale(1.15);
            box-shadow: 0 6px 20px rgba(139, 111, 71, 0.4);
        }
        .table-node:hover:not(.occupied) {
            transform: scale(1.1);
        }
        .table-legend {
            display: flex;
            justify-content: center;
            gap: 25px;
            margin-top: 20px;
            font-size: 14px;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #000;
        }
        .legend-dot {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 2px solid white;
        }
        .legend-dot.available { background: #a8c8a8; }
        .legend-dot.occupied { background: #d4a574; }
        .legend-dot.selected { background: #8b6f47; }
        .submit-btn-new {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #8b6f47 0%, #6d5436 100%);
            color: white;
            border: none;
            border-radius: 30px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            letter-spacing: 1px;
            margin-top: 20px;
            box-shadow: 0 8px 20px rgba(139, 111, 71, 0.3);
        }
        .submit-btn-new:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(139, 111, 71, 0.4);
        }
        .submit-btn-new:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        .category-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 30px;
            justify-content: center;
            padding: 20px 0;
        }
        .category-btn {
            padding: 12px 24px;
            border: 2px solid #8b6f47;
            background: white;
            color: #8b6f47;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Cormorant Garamond', serif;
            letter-spacing: 0.5px;
        }
        .category-btn:hover {
            background: rgba(139, 111, 71, 0.1);
            transform: translateY(-2px);
        }
        .category-btn.active {
            background: #8b6f47;
            color: white;
            box-shadow: 0 4px 15px rgba(139, 111, 71, 0.3);
        }
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .menu-item {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: all 0.3s;
            display: block;
        }
        .menu-item.hidden {
            display: none;
        }
        .menu-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.12);
        }
        .item-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .item-details h4 {
            font-family: 'Playfair Display', serif;
            font-size: 18px;
            color: #2d2d2d;
            margin: 0;
            font-weight: 600;
        }
        .item-details span {
            font-size: 16px;
            color: #8b6f47;
            font-weight: 600;
        }
        .qty-input {
            width: 100%;
            padding: 10px;
            border: 2px solid #e8e4dc;
            border-radius: 8px;
            font-size: 16px;
            text-align: center;
            font-family: 'Cormorant Garamond', serif;
        }
        .qty-input:focus {
            outline: none;
            border-color: #8b6f47;
            box-shadow: 0 0 0 3px rgba(139, 111, 71, 0.1);
        }
        @media (max-width: 968px) {
            .reservation-container-new {
                grid-template-columns: 1fr;
            }
            .time-slots {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 640px) {
            .time-slots {
                grid-template-columns: 1fr;
            }
        }

        /* Payment Step Styles */
        .payment-container-step {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px 30px;
        }

        .payment-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
        }

        .payment-title {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            font-weight: 700;
            color: #2d2d2d;
            margin-bottom: 10px;
            text-align: center;
        }

        .payment-subtitle {
            text-align: center;
            color: #6d6d6d;
            font-size: 16px;
            margin-bottom: 40px;
        }

        .booking-summary {
            background: #f9f7f3;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .summary-title {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            font-weight: 600;
            color: #2d2d2d;
            margin-bottom: 20px;
        }

        .payment-methods {
            margin-bottom: 30px;
        }

        .method-title {
            font-family: 'Playfair Display', serif;
            font-size: 20px;
            font-weight: 600;
            color: #2d2d2d;
            margin-bottom: 20px;
        }

        .payment-options {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .payment-option {
            border: 2px solid #e8e4dc;
            border-radius: 15px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }

        .payment-option:hover {
            border-color: #8b6f47;
            background: rgba(139, 111, 71, 0.05);
        }

        .payment-option.selected {
            border-color: #8b6f47;
            background: rgba(139, 111, 71, 0.1);
        }

        .payment-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }

        .payment-option-content {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .payment-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: #f9f7f3;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .payment-option.selected .payment-icon {
            background: #8b6f47;
            color: white;
        }

        .payment-info h4 {
            font-family: 'Playfair Display', serif;
            font-size: 18px;
            font-weight: 600;
            color: #2d2d2d;
            margin-bottom: 5px;
        }

        .payment-info p {
            font-size: 14px;
            color: #6d6d6d;
        }

        .bank-details {
            display: none;
            margin-top: 20px;
            padding: 20px;
            background: #fff8e1;
            border-radius: 12px;
            border-left: 4px solid #8b6f47;
        }

        .bank-details.show {
            display: block;
        }

        .bank-details h4 {
            font-family: 'Playfair Display', serif;
            font-size: 18px;
            font-weight: 600;
            color: #2d2d2d;
            margin-bottom: 15px;
        }

        .bank-info {
            display: grid;
            gap: 10px;
        }

        .bank-info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            align-items: center;
        }

        .bank-label {
            font-weight: 600;
            color: #5d5d5d;
        }

        .bank-value {
            color: #2d2d2d;
            font-weight: 500;
        }

        .copy-btn {
            background: #8b6f47;
            color: white;
            border: none;
            padding: 5px 15px;
            border-radius: 8px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s;
            margin-left: 10px;
        }

        .copy-btn:hover {
            background: #6d5436;
        }

        .action-buttons {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 15px;
            margin-top: 30px;
        }

        .btn-back {
            background: white;
            color: #8b6f47;
            border: 2px solid #8b6f47;
            padding: 18px;
            border-radius: 30px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }

        .btn-back:hover {
            background: #f9f7f3;
        }

        .btn-confirm {
            background: linear-gradient(135deg, #8b6f47 0%, #6d5436 100%);
            color: white;
            box-shadow: 0 8px 20px rgba(139, 111, 71, 0.3);
            padding: 18px;
            border-radius: 30px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            text-align: center;
        }

        .btn-confirm:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(139, 111, 71, 0.4);
        }

        .btn-confirm:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        /* Receipt Styles */
        .payment-section,
        .receipt-section {
            display: none;
        }

        .payment-section.active,
        .receipt-section.active {
            display: block;
        }

        .success-icon {
            text-align: center;
            margin-bottom: 30px;
        }

        .success-circle {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #86c98e 0%, #5da965 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            box-shadow: 0 8px 30px rgba(134, 201, 142, 0.4);
        }

        .success-circle i {
            font-size: 40px;
            color: white;
        }

        .receipt-title {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            font-weight: 700;
            color: #2d2d2d;
        }

        .receipt-subtitle {
            font-size: 16px;
            color: #6d6d6d;
            margin-top: 5px;
        }

        .receipt-number {
            display: inline-block;
            background: rgba(139, 111, 71, 0.1);
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            color: #8b6f47;
            margin-top: 15px;
        }

        .receipt-details {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-top: 25px;
        }

        .receipt-section-title {
            font-family: 'Playfair Display', serif;
            font-size: 18px;
            font-weight: 600;
            color: #2d2d2d;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .receipt-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f5f5f5;
        }

        .receipt-row:last-child {
            border-bottom: none;
        }

        .receipt-label {
            color: #6d6d6d;
            font-weight: 500;
        }

        .receipt-value {
            color: #2d2d2d;
            font-weight: 600;
            text-align: right;
        }

        .payment-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 13px;
            font-weight: 600;
        }

        .payment-badge.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .receipt-total {
            background: rgba(139, 111, 71, 0.05);
            padding: 20px;
            border-radius: 12px;
            margin-top: 20px;
        }

        .receipt-total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
        }

        .receipt-total-row.final {
            border-top: 2px solid #8b6f47;
            margin-top: 10px;
            padding-top: 12px;
        }

        .receipt-total-row.final .receipt-label {
            font-family: 'Playfair Display', serif;
            font-size: 20px;
            font-weight: 700;
            color: #2d2d2d;
        }

        .receipt-total-row.final .receipt-value {
            font-family: 'Playfair Display', serif;
            font-size: 24px;
            font-weight: 700;
            color: #8b6f47;
        }

        .receipt-note {
            background: #fff8e1;
            border-left: 4px solid #8b6f47;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 14px;
            color: #5d5d5d;
            line-height: 1.6;
        }

        .btn-print {
            background: white;
            color: #8b6f47;
            border: 2px solid #8b6f47;
            padding: 18px;
            border-radius: 30px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }

        .btn-print:hover {
            background: #f9f7f3;
        }

        @media (max-width: 768px) {
            .payment-options {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                grid-template-columns: 1fr;
            }
        }

        /* ========================================================================
           FULL-WIDTH LAYOUT - USES ENTIRE SCREEN SPACE
           ======================================================================== */

        /* Override parent container constraints for full-width */
        .reservation-page-section {
            align-items: stretch !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
        }

        .reservation-progress-container {
            max-width: 100% !important;
            padding: 0 30px !important;
        }

        .reservation-box {
            max-width: 100% !important;
            width: 100% !important;
            margin: 0 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            background: transparent !important;
        }

        .form-step {
            padding: 0 !important;
            background: transparent !important;
            width: 100% !important;
        }

        .reservation-fullwidth {
            width: 100%;
            max-width: 100%;
            padding: 40px 30px;
            background: #f5f5f0;
            margin: 0;
        }

        .reservation-grid-fullwidth {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
            width: 100%;
            max-width: none; /* Remove max-width restriction */
        }

        .column-fullwidth {
            background: white;
            border-radius: 25px;
            padding: 30px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            min-height: 550px;
        }

        .column-fullwidth:hover {
            box-shadow: 0 10px 35px rgba(0, 0, 0, 0.12);
            transform: translateY(-5px);
        }

        .column-header-full {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 3px solid #f0f0f0;
        }

        .column-header-full i {
            font-size: 40px;
            color: #8b6f47;
            margin-bottom: 15px;
            display: block;
        }

        .column-header-full h3 {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            font-weight: 700;
            color: #2d2d2d;
            margin: 0;
            letter-spacing: 0.5px;
        }

        .column-body-full {
            flex: 1;
            display: flex;
            flex-direction: column;
            pointer-events: auto;
        }

        /* Date Input */
        .date-input-full {
            width: 100%;
            padding: 18px;
            border: 3px solid #e0e0e0;
            border-radius: 15px;
            font-size: 16px;
            font-family: inherit;
            color: #2d2d2d;
            transition: all 0.3s;
            background: white;
            font-weight: 600;
        }

        .date-input-full:focus {
            border-color: #8b6f47;
            outline: none;
            box-shadow: 0 0 0 4px rgba(139, 111, 71, 0.1);
        }

        .info-box-full {
            margin-top: 20px;
            padding: 18px;
            background: #f0f7ff;
            border-radius: 12px;
            text-align: center;
            color: #2c3e50;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 500;
        }

        .info-box-full i {
            color: #8b6f47;
            font-size: 18px;
        }

        /* Scrollable Areas */
        .scrollable-area-full {
            flex: 1;
            overflow-y: auto;
            padding-right: 8px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            pointer-events: auto;
        }

        .time-slot-btn {
            padding: 18px;
            border: 3px solid #e0e0e0;
            background: white;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 700;
            color: #2d2d2d;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 15px;
            flex-shrink: 0;
            pointer-events: auto;
            position: relative;
            z-index: 1;
        }

        .time-slot-btn:hover:not(.booked) {
            border-color: #8b6f47;
            background: #f9f7f3;
            transform: translateX(5px);
        }

        .time-slot-btn.selected {
            background: linear-gradient(135deg, #8b6f47 0%, #6d5436 100%);
            color: white;
            border-color: #8b6f47;
            box-shadow: 0 5px 20px rgba(139, 111, 71, 0.3);
        }

        .time-slot-btn.booked {
            background: #ffe6e6;
            border-color: #ffcccc;
            color: #999;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .time-slot-label {
            font-size: 15px;
        }

        .time-slot-status {
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Party Size */
        .party-controls-full {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 25px;
            margin-bottom: 30px;
        }

        .party-ctrl-btn {
            width: 50px;
            height: 50px;
            border: 3px solid #8b6f47;
            background: white;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #8b6f47;
            font-size: 18px;
        }

        .party-ctrl-btn:hover {
            background: #8b6f47;
            color: white;
            transform: scale(1.15);
        }

        .party-counter-full {
            text-align: center;
        }

        .party-num-full {
            display: block;
            font-size: 56px;
            font-weight: 800;
            color: #8b6f47;
            line-height: 1;
        }

        .party-text-full {
            display: block;
            font-size: 16px;
            color: #6d6d6d;
            margin-top: 8px;
            font-weight: 600;
        }

        .quick-select-full {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }

        .quick-btn-full {
            padding: 18px;
            border: 3px solid #e0e0e0;
            background: white;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 700;
            color: #2d2d2d;
            font-size: 18px;
        }

        .quick-btn-full:hover {
            border-color: #8b6f47;
            background: #f9f7f3;
            transform: scale(1.05);
        }

        .quick-btn-full.active {
            background: #8b6f47;
            color: white;
            border-color: #8b6f47;
            box-shadow: 0 5px 20px rgba(139, 111, 71, 0.3);
        }

        /* Table Grid */
        .scrollable-area-full .table-card-full {
            padding: 20px;
            border: 3px solid #e0e0e0;
            background: white;
            border-radius: 15px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            margin-bottom: 12px;
        }

        .table-card-full:hover:not(.booked) {
            border-color: #8b6f47;
            background: #f9f7f3;
            transform: scale(1.05);
        }

        .table-card-full.selected {
            background: linear-gradient(135deg, #8b6f47 0%, #6d5436 100%);
            color: white;
            border-color: #8b6f47;
            box-shadow: 0 5px 20px rgba(139, 111, 71, 0.3);
        }

        .table-card-full.booked {
            background: #ffe6e6;
            border-color: #ffcccc;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .table-num-full {
            font-size: 26px;
            font-weight: 800;
            color: #8b6f47;
            margin-bottom: 8px;
        }

        .table-card-full.selected .table-num-full {
            color: white;
        }

        .table-cap-full {
            font-size: 14px;
            color: #6d6d6d;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-weight: 600;
        }

        .table-card-full.selected .table-cap-full {
            color: rgba(255, 255, 255, 0.95);
        }

        .table-booked-by {
            font-size: 12px;
            color: #e74c3c;
            margin-top: 8px;
            font-weight: 700;
        }

        /* Legend */
        .legend-full {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
            flex-shrink: 0;
        }

        .legend-item-full {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #6d6d6d;
            font-weight: 600;
        }

        .dot-full {
            width: 14px;
            height: 14px;
            border-radius: 50%;
        }

        .dot-full.available {
            background: #86c98e;
        }

        .dot-full.booked {
            background: #e74c3c;
        }

        /* Loading */
        .loading-box-full {
            text-align: center;
            padding: 50px 20px;
            color: #6d6d6d;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }

        .loading-box-full i {
            font-size: 40px;
            color: #8b6f47;
        }

        .loading-box-full span {
            font-size: 15px;
            font-weight: 600;
        }

        /* Summary Bar */
        .summary-bar-full {
            background: linear-gradient(135deg, #2d2d2d 0%, #1a1a1a 100%);
            padding: 25px 40px;
            margin: 30px 0 0 0;
            width: 100%;
            max-width: 100%;
            border-radius: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.25);
        }

        .summary-left-full {
            display: flex;
            gap: 35px;
        }

        .summary-chip-full {
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
            background: rgba(255, 255, 255, 0.1);
            padding: 12px 20px;
            border-radius: 15px;
            transition: all 0.3s;
        }

        .summary-chip-full:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .summary-chip-full i {
            color: #8b6f47;
            font-size: 20px;
        }

        .summary-chip-full span {
            font-weight: 700;
            font-size: 15px;
        }

        .next-btn-full {
            background: linear-gradient(135deg, #8b6f47 0%, #6d5436 100%);
            color: white;
            border: none;
            padding: 18px 40px;
            border-radius: 30px;
            font-size: 16px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 12px;
            letter-spacing: 0.5px;
        }

        .next-btn-full:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(139, 111, 71, 0.5);
        }

        .next-btn-full:disabled {
            opacity: 0.4;
            cursor: not-allowed;
            transform: none;
        }

        /* Scrollbar */
        .scrollable-area-full::-webkit-scrollbar {
            width: 8px;
        }

        .scrollable-area-full::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .scrollable-area-full::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #8b6f47, #6d5436);
            border-radius: 10px;
        }

        .scrollable-area-full::-webkit-scrollbar-thumb:hover {
            background: #6d5436;
        }

        /* Responsive */
        @media (max-width: 1400px) {
            .reservation-fullwidth {
                padding: 30px 20px;
            }
            
            .reservation-grid-fullwidth {
                gap: 20px;
            }
        }

        @media (max-width: 1200px) {
            .reservation-grid-fullwidth {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .column-fullwidth {
                min-height: 480px;
            }
        }

        @media (max-width: 768px) {
            .reservation-fullwidth {
                padding: 20px 15px;
            }
            
            .reservation-grid-fullwidth {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .column-fullwidth {
                padding: 20px;
                min-height: 420px;
            }
            
            .column-header-full i {
                font-size: 32px;
            }
            
            .column-header-full h3 {
                font-size: 18px;
            }
            
            .party-num-full {
                font-size: 48px;
            }
            
            .summary-bar-full {
                flex-direction: column;
                gap: 20px;
                padding: 20px;
                margin: 20px 0 0 0;
                width: 100%;
            }
            
            .summary-left-full {
                flex-wrap: wrap;
                gap: 10px;
                width: 100%;
                justify-content: center;
            }
            
            .summary-chip-full {
                padding: 10px 15px;
            }
            
            .next-btn-full {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .quick-select-full {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>

<!-- Payment Success Modal -->
<div id="payment-success-modal" class="payment-success-modal">
    <div class="payment-success-content">
        <div class="payment-success-icon">
            <i class="fas fa-check"></i>
        </div>
        <h2>Payment Successful!</h2>
        <p>Your payment has been processed successfully</p>
        <div class="payment-success-amount" id="modal-amount">RM 0.00</div>
        <div class="payment-success-details">
            <div class="payment-success-row">
                <span class="payment-success-label">Payment Method</span>
                <span class="payment-success-value" id="modal-payment-method">-</span>
            </div>
            <div class="payment-success-row">
                <span class="payment-success-label">Reservation Date</span>
                <span class="payment-success-value" id="modal-date">-</span>
            </div>
            <div class="payment-success-row">
                <span class="payment-success-label">Table</span>
                <span class="payment-success-value" id="modal-table">-</span>
            </div>
        </div>
        <p style="font-size: 14px; color: #86c98e; margin: 15px 0;">
            <i class="fas fa-check-circle"></i> Redirecting to your receipt...
        </p>
        <button class="payment-success-btn" onclick="closePaymentModal()">
            View Receipt
        </button>
    </div>
</div>


<!-- Navigation -->
<nav>
    <div class="logo">
        <i class="fa-solid fa-utensils logo-icon"></i>
        <div class="logo-text">
            <span class="logo-small">THE</span>
            <span class="logo-large">WELLINGTON</span>
        </div>
    </div>
    <ul class="nav-links">
        <li><a href="index.php#home">HOME</a></li>
        <li><a href="reservation.php" class="active">RESERVATION</a></li>
        <li><a href="about.php">ABOUT US</a></li>
        <li><a href="index.php#contact">CONTACT</a></li>
        <li><a href="menu.php">MENU</a></li>
    </ul>
    
    <?php if(isset($_SESSION['user'])): ?>
        <div class="user-welcome">
            <span class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User'); ?>!</span>
        <button class="btn-login" onclick="logout()">LOGOUT</button>
        </div>
    <?php else: ?>
        <a href="account.php" class="btn-login account-link">LOGIN</a>
    <?php endif; ?>
</nav>

<!-- Reservation Section -->
<section class="reservation-page-section">
    <div class="reservation-progress-container">
        <div class="progress-steps">
            <div class="progress-step" id="step-ind-1">
                <div class="step-circle active">1</div>
                <div class="step-label">Details & Table</div>
            </div>
            <div class="progress-line"></div>
            <div class="progress-step" id="step-ind-2">
                <div class="step-circle">2</div>
                <div class="step-label">Menu</div>
            </div>
            <div class="progress-line"></div>
            <div class="progress-step" id="step-ind-3">
                <div class="step-circle">3</div>
                <div class="step-label">Payment</div>
            </div>
            <div class="progress-line"></div>
            <div class="progress-step" id="step-ind-4">
                <div class="step-circle">4</div>
                <div class="step-label">Receipt</div>
            </div>
        </div>
    </div>
    
    <div class="reservation-box">
        <form id="bookingForm">
            <!-- STEP 1: Details & Table Selection (Combined) -->
            <div id="step-1" class="form-step">
                <!-- ========================================================================
                     SIMPLIFIED RESERVATION DETAILS - 4 COLUMN LAYOUT
                     ======================================================================== -->

                <!-- ========================================================================
                     FULL-WIDTH 4-COLUMN LAYOUT - USES ENTIRE SCREEN
                     ======================================================================== -->

                <div class="reservation-fullwidth">
                    <div class="reservation-grid-fullwidth">
                        <!-- Column 1: Select Date -->
                        <div class="column-fullwidth">
                            <div class="column-header-full">
                                <i class="fas fa-calendar-alt"></i>
                                <h3>Select Date</h3>
                            </div>
                            <div class="column-body-full">
                                <input type="date" 
                                       id="reservation_date" 
                                       class="date-input-full" 
                                       min="<?php echo date('Y-m-d'); ?>"
                                       onchange="loadAvailability()">
                                
                                <div id="selected-date-display" class="info-box-full">
                                    <i class="fas fa-info-circle"></i>
                                    <span>Please select a date</span>
                            </div>
                            </div>
                        </div>

                        <!-- Column 2: Select Time -->
                        <div class="column-fullwidth">
                            <div class="column-header-full">
                                <i class="fas fa-clock"></i>
                                <h3>Select Time</h3>
                            </div>
                            <div class="column-body-full">
                                <div id="time-slots-container" class="scrollable-area-full">
                                    <div class="loading-box-full">
                                        <i class="fas fa-spinner fa-spin"></i>
                                        <span>Select a date first</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Column 3: Party Size -->
                        <div class="column-fullwidth">
                            <div class="column-header-full">
                                <i class="fas fa-users"></i>
                                <h3>Party Size</h3>
                            </div>
                            <div class="column-body-full">
                                <div class="party-controls-full">
                                    <button type="button" class="party-ctrl-btn" onclick="decrementGuests()">
                                    <i class="fas fa-minus"></i>
                                </button>
                                    <div class="party-counter-full">
                                        <span class="party-num-full" id="party-number">2</span>
                                        <span class="party-text-full">Guests</span>
                                </div>
                                    <button type="button" class="party-ctrl-btn" onclick="incrementGuests()">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                        <!-- Column 4: Select Table -->
                        <div class="column-fullwidth">
                            <div class="column-header-full">
                                <i class="fas fa-chair"></i>
                                <h3>Select Table</h3>
                            </div>
                            <div class="column-body-full">
                                <div id="table-grid-container" class="scrollable-area-full">
                                    <div class="loading-box-full">
                                        <i class="fas fa-spinner fa-spin"></i>
                                        <span>Select date & time first</span>
                            </div>
                        </div>

                                <div class="legend-full">
                                    <div class="legend-item-full">
                                        <span class="dot-full available"></span>
                                    <span>Available</span>
                                </div>
                                    <div class="legend-item-full">
                                        <span class="dot-full booked"></span>
                                        <span>Booked</span>
                                </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hidden inputs -->
                <input type="hidden" id="date" value="">
                <input type="hidden" id="time" value="">
                <input type="hidden" id="guests" value="2">
                <input type="hidden" id="selected_table" value="">

                <!-- Summary Bar -->
                <div class="summary-bar-full">
                    <div class="summary-left-full">
                        <div class="summary-chip-full">
                            <i class="fas fa-calendar"></i>
                            <span id="summary-date">--</span>
                        </div>
                        <div class="summary-chip-full">
                            <i class="fas fa-clock"></i>
                            <span id="summary-time">--</span>
                        </div>
                        <div class="summary-chip-full">
                            <i class="fas fa-users"></i>
                            <span id="summary-guests">2 Guests</span>
                        </div>
                        <div class="summary-chip-full">
                            <i class="fas fa-chair"></i>
                            <span id="summary-table">--</span>
                        </div>
                    </div>
                    <button type="button" class="next-btn-full" onclick="goToStep2()" id="next-to-menu" disabled>
                        NEXT: PRE-ORDER MENU <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- STEP 2: Menu Selection -->
            <div id="step-2" class="form-step hidden">
                <h3 class="step-title">Pre-Order Your Meal</h3>
                <p class="step-subtitle">Food will be ready upon arrival. Select items to add to your order.</p>
                
                <!-- Category Filter Buttons -->
                <div class="category-filters">
                    <button type="button" class="category-btn active" data-category="all" onclick="filterMenuByCategory('all')">All</button>
                    <button type="button" class="category-btn" data-category="Main" onclick="filterMenuByCategory('Main')">Main</button>
                    <button type="button" class="category-btn" data-category="Starter" onclick="filterMenuByCategory('Starter')">Starter</button>
                    <button type="button" class="category-btn" data-category="Pasta" onclick="filterMenuByCategory('Pasta')">Pasta</button>
                    <button type="button" class="category-btn" data-category="Side" onclick="filterMenuByCategory('Side')">Side</button>
                    <button type="button" class="category-btn" data-category="Dessert" onclick="filterMenuByCategory('Dessert')">Dessert</button>
                    <button type="button" class="category-btn" data-category="Beverages" onclick="filterMenuByCategory('Beverages')">Beverages</button>
                </div>
                
                <div class="menu-grid">
                    <!-- Main Dishes -->
                    <div class="menu-item" data-category="Main">
                        <div class="item-details"><h4>Beef Wellington</h4><span>RM 55</span></div>
                        <input type="number" class="food-item qty-input" data-name="Beef Wellington" data-price="55" value="0" min="0">
                    </div>
                    <div class="menu-item" data-category="Main">
                        <div class="item-details"><h4>Chicken Wellington</h4><span>RM 32</span></div>
                        <input type="number" class="food-item qty-input" data-name="Chicken Wellington" data-price="32" value="0" min="0">
                    </div>
                    <div class="menu-item" data-category="Main">
                        <div class="item-details"><h4>Salmon Wellington</h4><span>RM 48</span></div>
                        <input type="number" class="food-item qty-input" data-name="Salmon Wellington" data-price="48" value="0" min="0">
                    </div>
                    <div class="menu-item" data-category="Main">
                        <div class="item-details"><h4>Mini Mushroom Wellington</h4><span>RM 25</span></div>
                        <input type="number" class="food-item qty-input" data-name="Mini Mushroom Wellington" data-price="25" value="0" min="0">
                    </div>
                    <div class="menu-item" data-category="Main">
                        <div class="item-details"><h4>Grilled Ribeye Steak</h4><span>RM 58</span></div>
                        <input type="number" class="food-item qty-input" data-name="Grilled Ribeye Steak" data-price="58" value="0" min="0">
                    </div>
                    <div class="menu-item" data-category="Main">
                        <div class="item-details"><h4>Herb-Roasted Chicken</h4><span>RM 28</span></div>
                        <input type="number" class="food-item qty-input" data-name="Herb-Roasted Chicken" data-price="28" value="0" min="0">
                    </div>
                    <div class="menu-item" data-category="Main">
                        <div class="item-details"><h4>Pan-Seared Seabass</h4><span>RM 35</span></div>
                        <input type="number" class="food-item qty-input" data-name="Pan-Seared Seabass" data-price="35" value="0" min="0">
                    </div>

                    <!-- Pasta -->
                    <div class="menu-item" data-category="Pasta">
                        <div class="item-details"><h4>Spaghetti Carbonara</h4><span>RM 22</span></div>
                        <input type="number" class="food-item qty-input" data-name="Spaghetti Carbonara" data-price="22" value="0" min="0">
                    </div>
                    <div class="menu-item" data-category="Pasta">
                        <div class="item-details"><h4>Spaghetti Aglio Olio</h4><span>RM 24</span></div>
                        <input type="number" class="food-item qty-input" data-name="Spaghetti Aglio Olio" data-price="24" value="0" min="0">
                    </div>

                    <!-- Starters -->
                    <div class="menu-item" data-category="Starter">
                        <div class="item-details"><h4>Classic Caesar Salad</h4><span>RM 16</span></div>
                        <input type="number" class="food-item qty-input" data-name="Classic Caesar Salad" data-price="16" value="0" min="0">
                    </div>
                    <div class="menu-item" data-category="Starter">
                        <div class="item-details"><h4>Wild Mushroom Soup</h4><span>RM 14</span></div>
                        <input type="number" class="food-item qty-input" data-name="Wild Mushroom Soup" data-price="14" value="0" min="0">
                    </div>
                    <div class="menu-item" data-category="Starter">
                        <div class="item-details"><h4>Bruschetta</h4><span>RM 12</span></div>
                        <input type="number" class="food-item qty-input" data-name="Bruschetta" data-price="12" value="0" min="0">
                    </div>
                    <div class="menu-item" data-category="Starter">
                        <div class="item-details"><h4>Smoked Salmon Bites</h4><span>RM 18</span></div>
                        <input type="number" class="food-item qty-input" data-name="Smoked Salmon Bites" data-price="18" value="0" min="0">
                    </div>
                    <div class="menu-item" data-category="Starter">
                        <div class="item-details"><h4>Garlic Herb Bread Basket</h4><span>RM 10</span></div>
                        <input type="number" class="food-item qty-input" data-name="Garlic Herb Bread Basket" data-price="10" value="0" min="0">
                    </div>

                    <!-- Sides -->
                    <div class="menu-item" data-category="Side">
                        <div class="item-details"><h4>Truffle Fries</h4><span>RM 12</span></div>
                        <input type="number" class="food-item qty-input" data-name="Truffle Fries" data-price="12" value="0" min="0">
                    </div>
                    <div class="menu-item" data-category="Side">
                        <div class="item-details"><h4>Creamy Mashed Potatoes</h4><span>RM 12</span></div>
                        <input type="number" class="food-item qty-input" data-name="Creamy Mashed Potatoes" data-price="12" value="0" min="0">
                    </div>
                    <div class="menu-item" data-category="Side">
                        <div class="item-details"><h4>Grilled Asparagus</h4><span>RM 18</span></div>
                        <input type="number" class="food-item qty-input" data-name="Grilled Asparagus" data-price="18" value="0" min="0">
                    </div>
                    <div class="menu-item" data-category="Side">
                        <div class="item-details"><h4>Three-Cheese Mac & Cheese</h4><span>RM 16</span></div>
                        <input type="number" class="food-item qty-input" data-name="Three-Cheese Mac & Cheese" data-price="16" value="0" min="0">
                    </div>
                    <div class="menu-item" data-category="Side">
                        <div class="item-details"><h4>Sautéed Wild Mushrooms</h4><span>RM 14</span></div>
                        <input type="number" class="food-item qty-input" data-name="Sautéed Wild Mushrooms" data-price="14" value="0" min="0">
                    </div>
                    <div class="menu-item" data-category="Side">
                        <div class="item-details"><h4>Creamed Spinach</h4><span>RM 14</span></div>
                        <input type="number" class="food-item qty-input" data-name="Creamed Spinach" data-price="14" value="0" min="0">
                    </div>

                    <!-- Desserts -->
                    <div class="menu-item" data-category="Dessert">
                        <div class="item-details"><h4>Crème Brûlée</h4><span>RM 16</span></div>
                        <input type="number" class="food-item qty-input" data-name="Crème Brûlée" data-price="16" value="0" min="0">
                    </div>
                    <div class="menu-item" data-category="Dessert">
                        <div class="item-details"><h4>Molten Chocolate Cake</h4><span>RM 18</span></div>
                        <input type="number" class="food-item qty-input" data-name="Molten Chocolate Cake" data-price="18" value="0" min="0">
                    </div>
                    <div class="menu-item" data-category="Dessert">
                        <div class="item-details"><h4>Tiramisu Wellington Style</h4><span>RM 20</span></div>
                        <input type="number" class="food-item qty-input" data-name="Tiramisu Wellington Style" data-price="20" value="0" min="0">
                    </div>

                    <!-- Beverages -->
                    <div class="menu-item" data-category="Beverages">
                        <div class="item-details"><h4>Peppermint Tea</h4><span>RM 8</span></div>
                        <input type="number" class="food-item qty-input" data-name="Peppermint Tea" data-price="8" value="0" min="0">
                    </div>
                    <div class="menu-item" data-category="Beverages">
                        <div class="item-details"><h4>Green Tea</h4><span>RM 9</span></div>
                        <input type="number" class="food-item qty-input" data-name="Green Tea" data-price="9" value="0" min="0">
                    </div>
                    <div class="menu-item" data-category="Beverages">
                        <div class="item-details"><h4>Americano</h4><span>RM 9</span></div>
                        <input type="number" class="food-item qty-input" data-name="Americano" data-price="9" value="0" min="0">
                    </div>
                    <div class="menu-item" data-category="Beverages">
                        <div class="item-details"><h4>Espresso</h4><span>RM 7</span></div>
                        <input type="number" class="food-item qty-input" data-name="Espresso" data-price="7" value="0" min="0">
                    </div>
                    <div class="menu-item" data-category="Beverages">
                        <div class="item-details"><h4>Watermelon Juice</h4><span>RM 13</span></div>
                        <input type="number" class="food-item qty-input" data-name="Watermelon Juice" data-price="13" value="0" min="0">
                    </div>
                    <div class="menu-item" data-category="Beverages">
                        <div class="item-details"><h4>Green Detox Juice</h4><span>RM 16</span></div>
                        <input type="number" class="food-item qty-input" data-name="Green Detox Juice" data-price="16" value="0" min="0">
                    </div>
                    <div class="menu-item" data-category="Beverages">
                        <div class="item-details"><h4>Berry Blast Juice</h4><span>RM 16</span></div>
                        <input type="number" class="food-item qty-input" data-name="Berry Blast Juice" data-price="16" value="0" min="0">
                    </div>
                </div>

                <div class="step-buttons">
                    <button type="button" class="btn-action btn-secondary" onclick="goToStep1()">Back</button>
                    <button type="button" class="btn-action" onclick="goToStep3()">NEXT: PAYMENT</button>
                </div>
            </div>

            <!-- STEP 3: Payment -->
            <!-- STEP 3: Payment -->
            <div id="step-3" class="form-step hidden">
                <div class="payment-container-step">
                    <div class="payment-card">
                        <h1 class="payment-title">Payment Method</h1>
                        <p class="payment-subtitle">Choose how you'd like to complete your payment</p>

                        <!-- Payment Method Selection -->
                        <div class="payment-methods">
                            <h3 class="method-title">Select Payment Method</h3>
                            <div class="payment-options">
                                <!-- Touch 'n Go Payment -->
                                <label class="payment-option" for="payment-tng">
                                    <input type="radio" name="payment_method" id="payment-tng" value="touch_n_go">
                                    <div class="payment-option-content">
                                        <div class="payment-icon">
                                            <i class="fas fa-mobile-alt"></i>
                                        </div>
                                        <div class="payment-info">
                                            <h4>Touch 'n Go</h4>
                                            <p>Pay via eWallet</p>
                                        </div>
                                    </div>
                                </label>

                                <!-- Bank Transfer -->
                                <label class="payment-option" for="payment-bank">
                                    <input type="radio" name="payment_method" id="payment-bank" value="bank_transfer">
                                    <div class="payment-option-content">
                                        <div class="payment-icon">
                                            <i class="fas fa-university"></i>
                                        </div>
                                        <div class="payment-info">
                                            <h4>Bank Transfer</h4>
                                            <p>Transfer to our account</p>
                                        </div>
                                    </div>
                                </label>
                            </div>

                            <!-- Bank Transfer Details (Hidden by default) -->
                            <div id="bank-details" class="bank-details">
                                <h4><i class="fas fa-info-circle"></i> Bank Transfer Details</h4>
                                <div class="bank-info">
                                    <div class="bank-info-row">
                                        <span class="bank-label">Bank Name:</span>
                                        <span class="bank-value">Maybank</span>
                                    </div>
                                    <div class="bank-info-row">
                                        <span class="bank-label">Account Name:</span>
                                        <span class="bank-value">The Wellington Restaurant Sdn Bhd</span>
                                    </div>
                                    <div class="bank-info-row">
                                        <span class="bank-label">Account Number:</span>
                                        <div>
                                            <span class="bank-value" id="account-number">5642 1234 5678</span>
                                            <button type="button" class="copy-btn" onclick="copyAccountNumber()">
                                                <i class="fas fa-copy"></i> Copy
                                            </button>
                                        </div>
                                    </div>
                                    <div class="bank-info-row">
                                        <span class="bank-label">Amount:</span>
                                        <span class="bank-value" id="bank-amount">RM 0.00</span>
                                    </div>
                                </div>
                                <div style="margin-top: 15px; padding: 12px; background: rgba(139, 111, 71, 0.1); border-radius: 8px;">
                                    <p style="font-size: 14px; color: #5d5d5d;">
                                        <strong>Note:</strong> Please complete the bank transfer before arriving at the restaurant. 
                                        Keep your transaction receipt for verification.
                                    </p>
                                </div>
                            </div>

                            <!-- Touch 'n Go Payment Details (Simplified - No Form) -->
                            <div id="tng-details" class="bank-details">
                                <h4><i class="fas fa-mobile-alt"></i> Touch 'n Go Payment</h4>
                                
                                <!-- QR Code Section -->
                                <div style="text-align: center; margin: 25px 0; padding: 25px; background: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.08);">
                                    <p style="font-size: 16px; font-weight: 600; color: #2d2d2d; margin-bottom: 15px;">
                                        Scan QR Code to Pay
                                    </p>
                                    
                                    <!-- QR Code Placeholder - Replace with actual QR code -->
                                    <div style="width: 250px; height: 250px; margin: 0 auto; background: linear-gradient(135deg, #f9f7f3 0%, #fff 100%); border: 3px solid #8b6f47; border-radius: 15px; display: flex; align-items: center; justify-content: center; flex-direction: column; padding: 20px;">
                                        <i class="fas fa-qrcode" style="font-size: 120px; color: #8b6f47; margin-bottom: 15px;"></i>
                                        <p style="font-size: 14px; color: #6d6d6d; font-weight: 600;">The Wellington TnG QR</p>
                                    </div>
                                    
                                    <div style="margin-top: 20px; padding: 15px; background: rgba(139, 111, 71, 0.05); border-radius: 10px;">
                                        <p style="font-size: 18px; font-weight: 700; color: #8b6f47; margin-bottom: 5px;">
                                            Amount to Pay
                                        </p>
                                        <p style="font-size: 32px; font-weight: 700; color: #2d2d2d;">
                                            RM <span id="tng-amount">0.00</span>
                                        </p>
                                    </div>
                                </div>

                                <div style="margin-top: 20px; padding: 15px; background: rgba(134, 201, 142, 0.1); border-radius: 10px; border-left: 4px solid #86c98e;">
                                    <p style="font-size: 14px; color: #5d5d5d;">
                                        <i class="fas fa-check-circle" style="color: #86c98e;"></i> 
                                        <strong>Payment Steps:</strong>
                                    </p>
                                    <ol style="font-size: 14px; color: #5d5d5d; margin-left: 20px; margin-top: 8px; line-height: 1.8;">
                                        <li>Scan the QR code with your TnG eWallet app</li>
                                        <li>Confirm the amount (RM <span id="tng-amount-text">0.00</span>)</li>
                                        <li>Complete the payment in your app</li>
                                        <li>Click "Confirm Booking" below to proceed</li>
                                    </ol>
                                </div>
                                
                                <div style="margin-top: 15px; padding: 12px; background: rgba(255, 193, 7, 0.1); border-radius: 8px; border-left: 4px solid #ffc107;">
                                    <p style="font-size: 13px; color: #5d5d5d;">
                                        <i class="fas fa-info-circle" style="color: #ffc107;"></i>
                                        <strong>Note:</strong> Please complete your Touch 'n Go payment before clicking the confirm button.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="action-buttons">
                            <button type="button" class="btn btn-back" onclick="goToStep2()">
                                <i class="fas fa-arrow-left"></i> Back
                            </button>
                            <button type="button" class="btn btn-confirm" id="confirm-payment-btn" onclick="goToStep4()" disabled>
                                Confirm Booking <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 4: Receipt (Hidden by default, shown after successful payment) -->
            <div id="step-4" class="form-step hidden">
                <div class="payment-card">
                    <div class="success-icon">
                        <div class="success-circle">
                            <i class="fas fa-check"></i>
                        </div>
                        <h1 class="receipt-title">Payment Successful!</h1>
                        <p class="receipt-subtitle">Your reservation has been confirmed</p>
                        <div class="receipt-number" id="receipt-number">Reservation #0000</div>
                    </div>

                    <div class="receipt-details">
                        <h3 class="receipt-section-title">
                            <i class="fas fa-calendar-alt"></i> Reservation Details
                        </h3>
                        <div class="receipt-row">
                            <span class="receipt-label">Date & Time</span>
                            <span class="receipt-value" id="receipt-datetime">-</span>
                        </div>
                        <div class="receipt-row">
                            <span class="receipt-label">Table</span>
                            <span class="receipt-value" id="receipt-table">-</span>
                        </div>
                        <div class="receipt-row">
                            <span class="receipt-label">Guests</span>
                            <span class="receipt-value" id="receipt-guests">-</span>
                        </div>
                        <div class="receipt-row">
                            <span class="receipt-label">Payment Method</span>
                            <span class="receipt-value" id="receipt-payment-method">-</span>
                        </div>
                        <div class="receipt-row">
                            <span class="receipt-label">Payment Status</span>
                            <span class="receipt-value">
                                <span class="payment-badge pending" id="receipt-payment-status">Pending Verification</span>
                            </span>
                        </div>
                    </div>

                    <div class="receipt-details" style="margin-top: 20px;">
                        <h3 class="receipt-section-title">
                            <i class="fas fa-utensils"></i> Order Summary
                        </h3>
                        <div id="receipt-items">
                            <!-- Items will be populated by JavaScript -->
                        </div>
                        <div class="receipt-total">
                            <div class="receipt-total-row">
                                <span class="receipt-label">Subtotal</span>
                                <span class="receipt-value" id="receipt-subtotal">RM 0.00</span>
                            </div>
                            <div class="receipt-total-row final">
                                <span class="receipt-label">Total</span>
                                <span class="receipt-value" id="receipt-total">RM 0.00</span>
                            </div>
                        </div>
                    </div>

                    <div class="receipt-note">
                        <p><strong>Important:</strong></p>
                        <ul style="margin-left: 20px; margin-top: 8px;">
                            <li>Please arrive on time for your reservation</li>
                            <li>Bring a valid ID for verification</li>
                            <li>Your pre-order will be ready upon arrival</li>
                            <li>Payment verification may take up to 24 hours</li>
                        </ul>
                    </div>

                    <div class="action-buttons" style="margin-top: 30px;">
                        <button type="button" class="btn btn-print" onclick="window.print()">
                            <i class="fas fa-print"></i> Print Receipt
                        </button>
                        <button type="button" class="btn btn-confirm" onclick="window.location.href='index.php'">
                            <i class="fas fa-home"></i> Back to Home
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Floating Order Summary Toggle -->
    <button class="order-summary-toggle" onclick="toggleOrderSummary()" id="order-summary-toggle" title="View Order Summary">
        <i class="fa-solid fa-shopping-cart"></i>
        <span class="order-count" id="order-count-badge">0</span>
    </button>
    
    <!-- Order Summary Sidebar -->
    <div class="order-summary-sidebar" id="order-summary-sidebar">
        <div class="order-summary-header">
            <h3>Your Order</h3>
            <button class="close-summary" onclick="toggleOrderSummary()"><i class="fa-solid fa-times"></i></button>
        </div>
        <div class="order-summary-content" id="order-summary-content">
            <p class="empty-order-message">Complete your reservation first, then add menu items</p>
        </div>
        <div class="order-summary-footer">
            <div class="order-total">
                <span>Total:</span>
                <span id="order-total-display">RM 0.00</span>
            </div>
        </div>
    </div>
</section>

<!-- Confirmation Modal -->
<div id="confirmation-modal" class="confirmation-modal hidden">
    <div class="modal-overlay" onclick="closeConfirmationModal()"></div>
    <div class="modal-content">
        <div class="modal-icon-success">
            <i class="fa-solid fa-check"></i>
        </div>
        <h3 class="modal-title">Reservation Confirmed!</h3>
        <p class="modal-message">
            Your table has been reserved and your pre-order has been sent to the kitchen. 
            You'll receive a confirmation email shortly.
        </p>
        <div class="modal-confirmation-code">
            <strong>Confirmation Code:</strong> 
            <span id="confirmation-code" class="code-display"></span>
        </div>
        <button class="btn-modal-close" onclick="closeConfirmationModal()">Close</button>
    </div>
</div>

<!-- Footer -->
<footer class="main-footer">
    <div class="footer-content">
        <div class="footer-left">
            <p>&copy; 2024 THE WELLINGTON</p>
        </div>
        <div class="footer-center">
            <a href="#" class="social-icon"><i class="fa-brands fa-facebook"></i></a>
            <a href="#" class="social-icon"><i class="fa-brands fa-instagram"></i></a>
            <a href="#" class="social-icon"><i class="fa-brands fa-twitter"></i></a>
        </div>
        <div class="footer-right">
            <p>ALL RIGHTS RESERVED</p>
        </div>
    </div>
</footer>

<script src="script.js"></script>
<script>
// Custom Calendar and Reservation Logic
(function() {
    const unavailableDates = [];
    const unavailableTimeSlots = {};
    const occupiedTables = [];
    
    const tableLayout = [
        { number: 1, top: '20%', left: '20%' },
        { number: 2, top: '20%', left: '50%' },
        { number: 3, top: '20%', left: '80%' },
        { number: 4, top: '50%', left: '35%' },
        { number: 5, top: '50%', left: '65%' },
        { number: 6, top: '80%', left: '20%' },
        { number: 7, top: '80%', left: '50%' },
        { number: 8, top: '80%', left: '80%' }
    ];
    
    let selectedDate = null;
    let selectedTime = null;
    let selectedTable = null;
    let guestCount = 2;
    const today = new Date();
    let currentMonth = today.getMonth();
    let currentYear = today.getFullYear();
    
    const timeSlots = [
        '5:00 PM', '5:30 PM', '6:00 PM', '6:30 PM', '7:00 PM', '7:30 PM',
        '8:00 PM', '8:30 PM', '9:00 PM', '9:30 PM', '10:00 PM', '10:30 PM'
    ];
    
    const timeTo24Hour = {
        '5:00 PM': '17:00', '5:30 PM': '17:30', '6:00 PM': '18:00', '6:30 PM': '18:30',
        '7:00 PM': '19:00', '7:30 PM': '19:30', '8:00 PM': '20:00', '8:30 PM': '20:30',
        '9:00 PM': '21:00', '9:30 PM': '21:30', '10:00 PM': '22:00', '10:30 PM': '22:30'
    };
    
    document.addEventListener('DOMContentLoaded', function() {
        renderCalendar();
        renderTimeSlots();
        renderTables();
        setupEventListeners();
    });
    
    function setupEventListeners() {
        const prevBtn = document.getElementById('prev-month');
        const nextBtn = document.getElementById('next-month');
        const decreaseBtn = document.getElementById('decrease-guests');
        const increaseBtn = document.getElementById('increase-guests');
        
        if(prevBtn) prevBtn.addEventListener('click', () => {
            currentMonth--;
            if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            }
            renderCalendar();
        });
        
        if(nextBtn) nextBtn.addEventListener('click', () => {
            currentMonth++;
            if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            }
            renderCalendar();
        });
        
        if(decreaseBtn) decreaseBtn.addEventListener('click', () => {
            if (guestCount > 1) {
                guestCount--;
                document.getElementById('guest-count').textContent = guestCount;
                document.getElementById('guests').value = guestCount + ' People';
            }
        });
        
        if(increaseBtn) increaseBtn.addEventListener('click', () => {
            if (guestCount < 10) {
                guestCount++;
                document.getElementById('guest-count').textContent = guestCount;
                document.getElementById('guests').value = guestCount + ' People';
            }
        });
    }
    
    function renderCalendar() {
        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'];
        
        const monthEl = document.getElementById('calendar-month');
        if(monthEl) monthEl.textContent = `${monthNames[currentMonth]} ${currentYear}`;
        
        const firstDay = new Date(currentYear, currentMonth, 1).getDay();
        const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
        const todayDate = new Date();
        todayDate.setHours(0, 0, 0, 0);
        
        const calendarDays = document.getElementById('calendar-days');
        if(!calendarDays) return;
        calendarDays.innerHTML = '';
        
        for (let i = 0; i < firstDay; i++) {
            calendarDays.innerHTML += '<div></div>';
        }
        
        for (let day = 1; day <= daysInMonth; day++) {
            const currentDate = new Date(currentYear, currentMonth, day);
            const dateString = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const isPast = currentDate < todayDate;
            const isUnavailable = unavailableDates.includes(dateString);
            
            const dayClass = (isPast || isUnavailable) ? 'unavailable' : 'available';
            const isSelected = selectedDate === dateString ? 'selected' : '';
            
            calendarDays.innerHTML += `
                <div class="calendar-day ${dayClass} ${isSelected}" data-date="${dateString}" onclick="window.selectReservationDate('${dateString}', this)">
                    ${day}
                </div>
            `;
        }
    }
    
    window.selectReservationDate = function(dateString, element) {
        if (element.classList.contains('unavailable')) return;
        
        document.querySelectorAll('.calendar-day').forEach(d => d.classList.remove('selected'));
        element.classList.add('selected');
        selectedDate = dateString;
        document.getElementById('date').value = dateString;
        
        renderTimeSlots();
        checkFormCompletion();
    };
    
    function renderTimeSlots() {
        const timeSlotsContainer = document.getElementById('time-slots');
        if(!timeSlotsContainer) return;
        
        const unavailableForDate = unavailableTimeSlots[selectedDate] || [];
        
        timeSlotsContainer.innerHTML = timeSlots.map(time => {
            const isUnavailable = unavailableForDate.includes(time);
            const isSelected = selectedTime === time ? 'selected' : '';
            return `
                <div class="time-slot ${isUnavailable ? 'unavailable' : ''} ${isSelected}" data-time="${time}" onclick="window.selectReservationTime('${time}', this)">
                    ${time}
                </div>
            `;
        }).join('');
    }
    
    window.selectReservationTime = function(time, element) {
        if (element.classList.contains('unavailable')) return;
        
        document.querySelectorAll('.time-slot').forEach(t => t.classList.remove('selected'));
        element.classList.add('selected');
        selectedTime = time;
        document.getElementById('time').value = timeTo24Hour[time] || time;
        
        checkFormCompletion();
    };
    
    function renderTables() {
        const tableFloor = document.getElementById('table-floor');
        if(!tableFloor) return;
        
        tableFloor.innerHTML = '';
        
        tableLayout.forEach(table => {
            const isOccupied = occupiedTables.includes(table.number);
            const tableDiv = document.createElement('div');
            tableDiv.className = `table-node ${isOccupied ? 'occupied' : 'available'}`;
            tableDiv.style.top = table.top;
            tableDiv.style.left = table.left;
            tableDiv.dataset.table = table.number;
            tableDiv.textContent = table.number;
            
            tableDiv.addEventListener('click', function() {
                if (this.classList.contains('occupied')) return;
                
                document.querySelectorAll('.table-node').forEach(t => t.classList.remove('selected'));
                this.classList.add('selected');
                selectedTable = this.dataset.table;
                document.getElementById('selected_table').value = 'Table ' + selectedTable;
                
                checkFormCompletion();
            });
            
            tableFloor.appendChild(tableDiv);
        });
    }
    
    function checkFormCompletion() {
        const nextBtn = document.getElementById('next-to-menu');
        if(!nextBtn) return;
        
        const name = document.getElementById('cust_name')?.value;
        const email = document.getElementById('email')?.value;
        const phone = document.getElementById('phone')?.value;
        
        if(selectedDate && selectedTime && selectedTable && name && email && phone) {
            nextBtn.disabled = false;
        } else {
            nextBtn.disabled = true;
        }
    }
    
    // Watch for form input changes
    document.addEventListener('DOMContentLoaded', function() {
        ['cust_name', 'email', 'phone'].forEach(id => {
            const el = document.getElementById(id);
            if(el) el.addEventListener('input', checkFormCompletion);
        });
    });
})();

// Category Filter Function
function filterMenuByCategory(category) {
    // Update active button
    document.querySelectorAll('.category-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.dataset.category === category) {
            btn.classList.add('active');
        }
    });
    
    // Filter menu items
    const menuItems = document.querySelectorAll('.menu-item');
    menuItems.forEach(item => {
        if (category === 'all') {
            item.classList.remove('hidden');
        } else {
            if (item.dataset.category === category) {
                item.classList.remove('hidden');
            } else {
                item.classList.add('hidden');
            }
        }
    });
}

// Payment Step Functions
function goToStep3Payment() {
    // Calculate total and items
    let total = 0;
    let itemCount = 0;
    const orderItems = [];
    
    document.querySelectorAll('.food-item').forEach(item => {
        const qty = parseInt(item.value) || 0;
        if(qty > 0) {
            const price = parseFloat(item.dataset.price) || 0;
            total += qty * price;
            itemCount += qty;
            orderItems.push({
                name: item.dataset.name,
                qty: qty,
                price: price
            });
        }
    });

    // Format date
    const dateInputEl = document.getElementById('date');
    const timeInputEl = document.getElementById('time');
    const dateInput = dateInputEl ? dateInputEl.value : '';
    const timeInput = timeInputEl ? timeInputEl.value : '';
    const formattedDate = dateInput ? dateInput + ' at ' + timeInput : '';

    // Update payment summary with null checks
    const paymentSummaryDatetime = document.getElementById('payment-summary-datetime');
    if (paymentSummaryDatetime) paymentSummaryDatetime.textContent = formattedDate || 'Not set';
    
    const selectedTableEl = document.getElementById('selected_table');
    const paymentSummaryTable = document.getElementById('payment-summary-table');
    if (paymentSummaryTable) paymentSummaryTable.textContent = selectedTableEl?.value || 'Auto-assigned';
    
    const guestsEl = document.getElementById('guests');
    const paymentSummaryGuests = document.getElementById('payment-summary-guests');
    if (paymentSummaryGuests) paymentSummaryGuests.textContent = guestsEl?.value || 'Not set';
    
    const paymentSummaryItems = document.getElementById('payment-summary-items');
    if (paymentSummaryItems) paymentSummaryItems.textContent = `${itemCount} items`;
    
    const paymentSummaryTotal = document.getElementById('payment-summary-total');
    if (paymentSummaryTotal) paymentSummaryTotal.textContent = `RM ${total.toFixed(2)}`;
    
    const bankAmount = document.getElementById('bank-amount');
    if (bankAmount) bankAmount.textContent = `RM ${total.toFixed(2)}`;
    const tngAmount = document.getElementById('tng-amount');
    if (tngAmount) tngAmount.textContent = total.toFixed(2);
    const tngAmountText = document.getElementById('tng-amount-text');
    if (tngAmountText) tngAmountText.textContent = total.toFixed(2);

    // Hide all steps and show payment step
    const step1 = document.getElementById('step-1');
    const step2 = document.getElementById('step-2');
    const step3 = document.getElementById('step-3');
    const step4 = document.getElementById('step-4');
    if (step1) step1.classList.add('hidden');
    if (step2) step2.classList.add('hidden');
    if (step3) step3.classList.remove('hidden');
    if (step4) step4.classList.add('hidden');
    
    // Update progress to step 3
    if (typeof updateProgressStep === 'function') {
    updateProgressStep(3);
    }
    
    // Hide order summary
    if (typeof toggleOrderSummary === 'function') {
    toggleOrderSummary(false);
}

    // NEW: Show/hide payment options based on items
    const paymentMethodsSection = document.querySelector('.payment-methods');
    const confirmBtn = document.getElementById('confirm-payment-btn');
    
    if (itemCount === 0) {
        // No items - hide payment options and enable confirm button
        if (paymentMethodsSection) {
            paymentMethodsSection.style.display = 'none';
        }
        if (confirmBtn) {
            confirmBtn.disabled = false; // Enable button for reservation-only
            confirmBtn.innerHTML = '<i class="fas fa-check-circle"></i> CONFIRM RESERVATION';
        }
    } else {
        // Has items - show payment options
        if (paymentMethodsSection) {
            paymentMethodsSection.style.display = 'block';
        }
        if (confirmBtn) {
            confirmBtn.disabled = true; // Require payment method selection
            confirmBtn.innerHTML = '<i class="fas fa-check-circle"></i> CONFIRM & PAY';
        }
    }
    
    // Setup payment method selection listeners
    setupPaymentMethodListeners();
}

// Setup payment method selection listeners using event delegation
function setupPaymentMethodListeners() {
    // Use event delegation on payment methods container
    const paymentMethods = document.querySelector('.payment-methods');
    if (paymentMethods) {
        // Remove any existing listeners by using a named function
        paymentMethods.removeEventListener('click', handlePaymentOptionClick);
        paymentMethods.addEventListener('click', handlePaymentOptionClick);
    }
    
    // Also listen to radio button changes directly (more reliable)
    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
        radio.removeEventListener('change', handlePaymentMethodChange);
        radio.addEventListener('change', handlePaymentMethodChange);
    });
}

// Handle payment option click
function handlePaymentOptionClick(e) {
    const option = e.target.closest('.payment-option');
    if (!option) return;
    
    // Remove selected class from all options
    document.querySelectorAll('.payment-option').forEach(opt => {
        opt.classList.remove('selected');
    });
    
    // Add selected class to clicked option
    option.classList.add('selected');
    
    // Check the radio button
    const radio = option.querySelector('input[type="radio"]');
    if (radio) {
        radio.checked = true;
        radio.dispatchEvent(new Event('change'));
        
        // CRITICAL FIX: Also enable button directly as backup
        const confirmBtn = document.getElementById('confirm-payment-btn');
        if (confirmBtn) {
            confirmBtn.disabled = false;
            console.log('✅ Button enabled via click handler!');
        }
    }
}

// Handle payment method radio change
function handlePaymentMethodChange(e) {
    const radio = e.target;
    
    console.log('Payment method changed:', radio.value); // Debug log
    
    // Calculate total from selected menu items
    let total = 0;
    document.querySelectorAll('.food-item').forEach(item => {
        const qty = parseInt(item.value) || 0;
        if(qty > 0) {
            const price = parseFloat(item.dataset.price) || 0;
            total += qty * price;
        }
    });
    
    // Show/hide payment details
    const bankDetails = document.getElementById('bank-details');
    const tngDetails = document.getElementById('tng-details');
    
    if (bankDetails) bankDetails.classList.remove('show');
    if (tngDetails) tngDetails.classList.remove('show');
    
    if (radio.value === 'bank_transfer' && bankDetails) {
        bankDetails.classList.add('show');
        // Update bank transfer amount
        const bankAmount = document.getElementById('bank-amount');
        if (bankAmount) bankAmount.textContent = `RM ${total.toFixed(2)}`;
    } else if (radio.value === 'touch_n_go' && tngDetails) {
        tngDetails.classList.add('show');
        // Update Touch 'n Go amount to match selected menu items
        const tngAmount = document.getElementById('tng-amount');
        if (tngAmount) tngAmount.textContent = total.toFixed(2);
        const tngAmountText = document.getElementById('tng-amount-text');
        if (tngAmountText) tngAmountText.textContent = total.toFixed(2);
        console.log('Updated TnG amount to:', total.toFixed(2));
    }
    
    // Enable confirm button - CRITICAL FIX
    const confirmBtn = document.getElementById('confirm-payment-btn');
    if (confirmBtn) {
        confirmBtn.disabled = false;
        console.log('✅ Button enabled!'); // Debug log
    } else {
        console.error('❌ Confirm button not found!'); // Debug log
    }
}

// goToStep3 is now defined above in the STEP 2 section

// Show receipt after successful payment
function showReceipt(reservationId, reservationData) {
    // Calculate total
    let total = 0;
    let orderItems = [];
    
    document.querySelectorAll('.food-item').forEach(item => {
        const qty = parseInt(item.value) || 0;
        if(qty > 0) {
            const price = parseFloat(item.dataset.price) || 0;
            total += qty * price;
            orderItems.push({
                name: item.dataset.name,
                qty: qty,
                price: price
            });
        }
    });

    // Format date
    const dateInputEl = document.getElementById('date');
    const timeInputEl = document.getElementById('time');
    const dateInput = dateInputEl ? dateInputEl.value : '';
    const timeInput = timeInputEl ? timeInputEl.value : '';
    const formattedDate = dateInput ? dateInput + ' at ' + timeInput : '';

    // Get payment method
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
    const paymentMethodText = paymentMethod ? (paymentMethod.value === 'touch_n_go' ? 'Touch \'n Go' : 'Bank Transfer') : 'N/A';

    // Update receipt with null checks
    const receiptNumber = document.getElementById('receipt-number');
    if (receiptNumber) receiptNumber.textContent = `Reservation #${reservationId}`;
    
    const receiptDatetime = document.getElementById('receipt-datetime');
    if (receiptDatetime) receiptDatetime.textContent = formattedDate || 'Not set';
    
    const selectedTableEl = document.getElementById('selected_table');
    const receiptTable = document.getElementById('receipt-table');
    if (receiptTable) receiptTable.textContent = selectedTableEl?.value || 'Auto-assigned';
    
    const guestsEl = document.getElementById('guests');
    const receiptGuests = document.getElementById('receipt-guests');
    if (receiptGuests) receiptGuests.textContent = guestsEl?.value || 'Not set';
    
    const receiptPaymentMethod = document.getElementById('receipt-payment-method');
    if (receiptPaymentMethod) receiptPaymentMethod.textContent = paymentMethodText;
    
    const receiptSubtotal = document.getElementById('receipt-subtotal');
    if (receiptSubtotal) receiptSubtotal.textContent = `RM ${total.toFixed(2)}`;
    
    const receiptTotal = document.getElementById('receipt-total');
    if (receiptTotal) receiptTotal.textContent = `RM ${total.toFixed(2)}`;

    // Populate order items
    const receiptItems = document.getElementById('receipt-items');
    if (!receiptItems) {
        console.error('Receipt items container not found');
        return;
    }
    receiptItems.innerHTML = '';
    if (orderItems.length > 0) {
        orderItems.forEach(item => {
            const itemTotal = item.qty * item.price;
            const row = document.createElement('div');
            row.className = 'receipt-row';
            row.innerHTML = `
                <div>
                    <div style="font-weight: 600; color: #2d2d2d;">${item.name}</div>
                    <div style="font-size: 13px; color: #6d6d6d;">${item.qty} x RM ${item.price.toFixed(2)}</div>
                </div>
                <span class="receipt-value">RM ${itemTotal.toFixed(2)}</span>
            `;
            receiptItems.appendChild(row);
        });
    } else {
        receiptItems.innerHTML = '<div class="receipt-row"><span class="receipt-label">No items ordered</span></div>';
    }

    // Hide all steps and show receipt
    const step1 = document.getElementById('step-1');
    const step2 = document.getElementById('step-2');
    const step3 = document.getElementById('step-3');
    const step4 = document.getElementById('step-4');
    if (step1) step1.classList.add('hidden');
    if (step2) step2.classList.add('hidden');
    if (step3) step3.classList.add('hidden');
    if (step4) step4.classList.remove('hidden');
    
    // Update progress to step 4
    if (typeof updateProgressStep === 'function') {
    updateProgressStep(4);
    }
    
    // Mark step 3 as completed (if elements exist)
    const stepPayment = document.getElementById('step-payment');
    const stepReceipt = document.getElementById('step-receipt');
    if (stepPayment) stepPayment.classList.add('completed');
    if (stepReceipt) stepReceipt.classList.add('active');
    
    // Hide order summary
    toggleOrderSummary(false);
    
    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ========================================================================
// FIXED: Allow submission even without items (reservation only)
// ========================================================================

function goToStep4() {
    // Calculate order items
    let total = 0;
    const orderItems = [];
    
    document.querySelectorAll('.food-item').forEach(item => {
        const qty = parseInt(item.value) || 0;
        if(qty > 0) {
            const price = parseFloat(item.dataset.price) || 0;
            total += qty * price;
            orderItems.push({
                name: item.dataset.name,
                qty: qty,
                price: price
            });
        }
    });
    
    // Check if payment method is required
    let paymentMethod = '';
    if (orderItems.length > 0) {
        // Items ordered - payment method required
        const paymentMethodInput = document.querySelector('input[name="payment_method"]:checked');
        if (!paymentMethodInput) {
            alert('Please select a payment method for your pre-order items');
            return;
        }
        paymentMethod = paymentMethodInput.value;
    }
    // If no items, paymentMethod stays empty (allowed)

    // Prepare form data
    const formData = new FormData();
    
    // Get user information from session (since users are logged in)
    const userName = '<?php echo isset($_SESSION["full_name"]) ? addslashes($_SESSION["full_name"]) : (isset($_SESSION["username"]) ? addslashes($_SESSION["username"]) : ""); ?>';
    const userEmail = '<?php echo isset($_SESSION["email"]) ? addslashes($_SESSION["email"]) : ""; ?>';
    const userId = '<?php echo isset($_SESSION["user_id"]) ? intval($_SESSION["user_id"]) : 0; ?>';
    const isLoggedIn = userId > 0;
    
    // Basic information - use session data or fallback to form fields if they exist
    const finalName = userName || document.getElementById('cust_name')?.value || '';
    const finalEmail = userEmail || document.getElementById('email')?.value || '';
    const finalPhone = document.getElementById('phone')?.value || ''; // Phone can be empty if logged in
    
    formData.append('name', finalName);
    formData.append('email', finalEmail);
    formData.append('phone', finalPhone); // Backend will fetch from database if empty and user is logged in
    
    // Get reservation details from hidden inputs or global variables
    const reservationDate = selectedDate || document.getElementById('date')?.value || document.getElementById('reservation_date')?.value || '';
    const reservationTime = selectedTime || document.getElementById('time')?.value || '';
    const reservationGuests = selectedGuests || document.getElementById('guests')?.value || 2;
    const reservationTable = selectedTable ? ('Table ' + selectedTable) : (document.getElementById('selected_table')?.value || '');
    
    // Validate required fields
    if (!reservationDate || !reservationTime || !reservationTable) {
        alert('Please complete all reservation details (date, time, and table selection)');
        return;
    }
    
    // Validate user information - name and email are required
    // Phone is optional if user is logged in (backend will fetch from database)
    if (!finalName || !finalEmail) {
        console.error('Missing user info:', { 
            name: finalName, 
            email: finalEmail, 
            phone: finalPhone, 
            userId: userId,
            isLoggedIn: isLoggedIn,
            sessionName: userName,
            sessionEmail: userEmail
        });
        alert('User information is missing. Please ensure you are logged in.\n\nName: ' + (finalName ? '✓' : '✗') + '\nEmail: ' + (finalEmail ? '✓' : '✗'));
        return;
    }
    
    // Log for debugging
    console.log('Submitting reservation:', {
        name: finalName,
        email: finalEmail,
        phone: finalPhone || (isLoggedIn ? '(will be fetched by backend)' : 'MISSING'),
        date: reservationDate,
        time: reservationTime,
        guests: reservationGuests,
        table: reservationTable,
        userId: userId,
        isLoggedIn: isLoggedIn
    });
    
    formData.append('date', reservationDate);
    formData.append('time', reservationTime);
    formData.append('guests', reservationGuests);
    formData.append('table', reservationTable);
    formData.append('special_requests', document.getElementById('special-requests')?.value || '');
    formData.append('payment_method', paymentMethod);
    formData.append('pre_order_total', total.toFixed(2));
    formData.append('order_items', JSON.stringify(orderItems));
    
    // Disable the confirm button to prevent double submission
    const confirmBtn = document.getElementById('confirm-payment-btn');
    if (!confirmBtn) {
        console.error('Confirm button not found');
        alert('Error: Confirm button not found. Please refresh the page.');
        return;
    }
    const originalText = confirmBtn.innerHTML;
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    
    // Submit reservation
    fetch('reservation_process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                console.error('Non-JSON response:', text);
                throw new Error('Server returned non-JSON response: ' + text.substring(0, 200));
            });
        }
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.status === 'success') {
            // Show receipt
            if (typeof showReceipt === 'function') {
                showReceipt(data.reservation_id, data);
            } else if (typeof displayReceipt === 'function') {
                displayReceipt(data.reservation_id, data.points_awarded);
        } else {
                // Fallback: show success message
                showSuccessMessage(data);
            }
            
            // Show success message
            if (data.message) {
                console.log('Success:', data.message);
            }
        } else {
            // Show error with full message
            const errorMsg = data.message || 'An error occurred. Please try again.';
            console.error('Reservation error:', errorMsg);
            alert('Error: ' + errorMsg);
            if (confirmBtn) {
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = originalText;
            }
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        alert('An error occurred while processing your reservation.\n\nError: ' + error.message + '\n\nPlease check the browser console for more details.');
        if (confirmBtn) {
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalText;
        }
    });
}

function showPaymentSuccessModal(amount, paymentMethod) {
    // Update modal content
    const modalAmount = document.getElementById('modal-amount');
    if (modalAmount) modalAmount.textContent = 'RM ' + amount.toFixed(2);
    
    const modalPaymentMethod = document.getElementById('modal-payment-method');
    if (modalPaymentMethod) {
        modalPaymentMethod.textContent = paymentMethod === 'touch_n_go' ? 'Touch \'n Go' : 'Bank Transfer';
    }
    
    const dateInput = document.getElementById('date');
    const timeInput = document.getElementById('time');
    const modalDate = document.getElementById('modal-date');
    if (modalDate && dateInput && timeInput) {
        modalDate.textContent = dateInput.value + ' at ' + timeInput.value;
    }
    
    const selectedTable = document.getElementById('selected_table');
    const modalTable = document.getElementById('modal-table');
    if (modalTable) {
        modalTable.textContent = selectedTable?.value || 'Auto-assigned';
    }
    
    // Show modal with animation
    const modal = document.getElementById('payment-success-modal');
    if (modal) modal.classList.add('show');
}

function closePaymentModal() {
    const modal = document.getElementById('payment-success-modal');
    if (modal) modal.classList.remove('show');
    
    // Display receipt immediately
    const reservationId = sessionStorage.getItem('reservation_id');
    const pointsAwarded = sessionStorage.getItem('points_awarded') || 0;
    displayReceipt(reservationId, pointsAwarded);
}

function displayReceipt(reservationId, pointsAwarded) {
    // Hide all steps
    const step1 = document.getElementById('step-1');
    const step2 = document.getElementById('step-2');
    const step3 = document.getElementById('step-3');
    const step4 = document.getElementById('step-4');
    if (step1) step1.classList.add('hidden');
    if (step2) step2.classList.add('hidden');
    if (step3) step3.classList.add('hidden');
    if (step4) step4.classList.remove('hidden');
    
    if (typeof updateProgressStep === 'function') {
    updateProgressStep(4);
    }
    
    // Update receipt details
    const receiptNumber = document.getElementById('receipt-number');
    if (receiptNumber) receiptNumber.textContent = 'Reservation #' + reservationId;
    
    const dateInput = document.getElementById('date');
    const timeInput = document.getElementById('time');
    const receiptDatetime = document.getElementById('receipt-datetime');
    if (receiptDatetime && dateInput && timeInput) {
        receiptDatetime.textContent = dateInput.value + ' at ' + timeInput.value;
    }
    
    const selectedTable = document.getElementById('selected_table');
    const receiptTable = document.getElementById('receipt-table');
    if (receiptTable) {
        receiptTable.textContent = selectedTable?.value || 'Auto-assigned';
    }
    
    const guestsInput = document.getElementById('guests');
    const receiptGuests = document.getElementById('receipt-guests');
    if (receiptGuests && guestsInput) {
        receiptGuests.textContent = guestsInput.value;
    }
    
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
    const receiptPaymentMethod = document.getElementById('receipt-payment-method');
    if (receiptPaymentMethod && paymentMethod) {
        receiptPaymentMethod.textContent = paymentMethod.value === 'touch_n_go' ? 'Touch \'n Go' : 'Bank Transfer';
    }
    
    // Update order summary in receipt
    const receiptOrderList = document.getElementById('receipt-order-list');
    if (receiptOrderList) receiptOrderList.innerHTML = '';
    
    let total = 0;
    document.querySelectorAll('.food-item').forEach(item => {
        const qty = parseInt(item.value) || 0;
        if(qty > 0) {
            const price = parseFloat(item.dataset.price) || 0;
            const itemTotal = qty * price;
            total += itemTotal;
            
            const row = document.createElement('div');
            row.className = 'receipt-order-item';
            row.innerHTML = `
                <span>${item.dataset.name} x ${qty}</span>
                <span>RM ${itemTotal.toFixed(2)}</span>
            `;
            receiptOrderList.appendChild(row);
        }
    });
    
    document.getElementById('receipt-total').textContent = 'RM ' + total.toFixed(2);
    
    // Show points if awarded
    if (pointsAwarded > 0) {
        const pointsElement = document.getElementById('receipt-points');
        if (pointsElement) {
            pointsElement.textContent = pointsAwarded + ' points earned!';
            pointsElement.style.display = 'block';
        }
    }
    
    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Payment method selection handler
document.addEventListener('DOMContentLoaded', function() {
    // Setup payment method listeners
    setupPaymentMethodListeners();
});

// Copy account number function
function copyAccountNumber() {
    const accountNumber = document.getElementById('account-number').textContent;
    navigator.clipboard.writeText(accountNumber.replace(/\s/g, '')).then(() => {
        alert('Account number copied to clipboard!');
    }).catch(() => {
        // Fallback for older browsers
        const textarea = document.createElement('textarea');
        textarea.value = accountNumber.replace(/\s/g, '');
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        alert('Account number copied to clipboard!');
    });
}

// ========================================================================
// SIMPLIFIED RESERVATION JAVASCRIPT
// ========================================================================

// Global variables
let selectedDate = null;
let selectedTime = null;
let selectedGuests = 2;
let selectedTable = null;
let availabilityData = null;
let orderItems = [];
let orderTotal = 0;

// Load availability when date changes
function loadAvailability() {
    try {
        const dateInput = document.getElementById('reservation_date');
        if (!dateInput) {
            console.error('Date input not found');
            return;
        }
        
        selectedDate = dateInput.value;
        
        if (!selectedDate) {
            console.log('No date selected');
            return;
        }
        
        // Validate date format (should be YYYY-MM-DD)
        const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
        if (!dateRegex.test(selectedDate)) {
            console.error('Invalid date format:', selectedDate);
            alert('Invalid date format. Please select a valid date.');
            return;
        }
        
        // Update hidden input for form submission
        const hiddenDateInput = document.getElementById('date');
        if (hiddenDateInput) {
            hiddenDateInput.value = selectedDate;
        }
        
        // Update summary
        try {
            const summaryDateEl = document.getElementById('summary-date');
            if (summaryDateEl) {
                summaryDateEl.textContent = formatDate(selectedDate);
            }
        } catch (e) {
            console.error('Error formatting date:', e);
        }
        
        // Show loading for time slots
        const timeSlotsContainer = document.getElementById('time-slots-container');
        if (timeSlotsContainer) {
            timeSlotsContainer.innerHTML = `
                <div class="loading-box-full">
                    <i class="fas fa-spinner fa-spin"></i>
                    <span>Loading available times...</span>
                </div>
            `;
        }
        
        // Fetch availability for default time first
        const defaultTime = '18:00:00';
        fetchAvailability(selectedDate, defaultTime);
    } catch (error) {
        console.error('Error in loadAvailability:', error);
        alert('An error occurred while loading availability. Please try again.');
    }
}

// Fetch availability from server
function fetchAvailability(date, time) {
    if (!date || !time) {
        console.error('fetchAvailability: Missing date or time', { date, time });
        return;
    }
    
    const url = `check_availability.php?date=${encodeURIComponent(date)}&time=${encodeURIComponent(time)}`;
    console.log('Fetching availability:', url);
    
    fetch(url)
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers.get('content-type'));
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    console.error('Non-JSON response:', text);
                    throw new Error('Server returned non-JSON response: ' + text.substring(0, 100));
                });
            }
            
            return response.json();
        })
        .then(data => {
            console.log('Availability data received:', data);
            
            if (!data) {
                throw new Error('No data received from server');
            }
            
            if (data.success) {
                availabilityData = data;
                renderTimeSlots(data.time_slots || []);
                if (selectedTime && data.tables) {
                    renderTables(data.tables);
                }
            } else {
                const errorMsg = data.message || 'Unknown error occurred';
                console.error('Availability error:', errorMsg);
                alert('Error loading availability: ' + errorMsg);
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            alert('Failed to load availability. Please check your connection and try again.\n\nError: ' + error.message);
        });
}

// Render time slots
function renderTimeSlots(timeSlots) {
    const container = document.getElementById('time-slots-container');
    
    if (!timeSlots || timeSlots.length === 0) {
        container.innerHTML = `
            <div class="loading-box-full">
                <i class="fas fa-info-circle"></i>
                <span>No time slots available</span>
            </div>
        `;
        return;
    }
    
    container.innerHTML = timeSlots.map(slot => {
        const isBooked = !slot.available || slot.available_tables === 0;
        const isSelected = selectedTime === slot.time;
        
        return `
            <button type="button" 
                    class="time-slot-btn ${isBooked ? 'booked' : ''} ${isSelected ? 'selected' : ''}"
                    data-time="${slot.time}"
                    data-label="${slot.label}"
                    ${isBooked ? 'disabled' : ''}
                    onclick="selectTime('${slot.time}', '${slot.label}', this)">
                <span class="time-slot-label">${slot.label}</span>
                <span class="time-slot-status">
                    ${isBooked 
                        ? '<i class="fas fa-lock"></i> Full' 
                        : `<i class="fas fa-check"></i> ${slot.available_tables} tables`
                    }
                </span>
            </button>
        `;
    }).join('');
}

// Select time slot
function selectTime(time, label, element) {
    console.log('selectTime called:', time, label, element);
    
    if (!time || !label) {
        console.error('Invalid time or label:', time, label);
        return;
    }
    
    selectedTime = time;
    
    // Update hidden input for form submission
    const hiddenTimeInput = document.getElementById('time');
    if (hiddenTimeInput) {
        hiddenTimeInput.value = time;
    }
    
    // Update summary display
    const summaryTimeEl = document.getElementById('summary-time');
    if (summaryTimeEl) {
        summaryTimeEl.textContent = label;
    }
    
    // Update UI - remove selected class from all buttons
    document.querySelectorAll('.time-slot-btn').forEach(btn => {
        btn.classList.remove('selected');
    });
    
    // Select the clicked button
    if (element && element.classList) {
        element.classList.add('selected');
    } else {
        // Fallback: find button by data attribute
        document.querySelectorAll('.time-slot-btn').forEach(btn => {
            const btnTime = btn.getAttribute('data-time');
            if (btnTime === time) {
                btn.classList.add('selected');
            }
        });
    }
    
    // Load tables for selected date and time
    if (selectedDate && selectedTime) {
        fetchAvailability(selectedDate, selectedTime);
    }
    
    checkFormComplete();
}

// Render tables
function renderTables(tables) {
    const container = document.getElementById('table-grid-container');
    
    if (!tables || tables.length === 0) {
        container.innerHTML = `
            <div class="loading-box-full">
                <i class="fas fa-info-circle"></i>
                <span>No tables available</span>
            </div>
        `;
        return;
    }
    
    container.innerHTML = tables.map(table => {
        const isBooked = table.status === 'occupied';
        const isSelected = selectedTable === table.table_number;
        
        return `
            <div class="table-card-full ${isBooked ? 'booked' : ''} ${isSelected ? 'selected' : ''}"
                 onclick="${isBooked ? '' : `selectTable(${table.table_number}, ${table.capacity}, this)`}">
                <div class="table-num-full">Table ${table.table_number}</div>
                ${isBooked ? `
                    <div class="table-booked-by">
                        <i class="fas fa-user"></i> ${table.booked_by}
                    </div>
                ` : ''}
            </div>
        `;
    }).join('');
}

// Select table
function selectTable(tableNumber, capacity, element) {
    selectedTable = tableNumber;
    
    // Update summary
    document.getElementById('summary-table').textContent = `Table ${tableNumber}`;
    
    // Update hidden input
    document.getElementById('selected_table').value = `Table ${tableNumber}`;
    
    // Update UI
    document.querySelectorAll('.table-card-full').forEach(card => {
        card.classList.remove('selected');
    });
    
    // Select the clicked table card
    if (element) {
        element.classList.add('selected');
    } else {
        // Fallback: find card by table number
        document.querySelectorAll('.table-card-full').forEach(card => {
            const cardText = card.textContent;
            if (cardText.includes(`Table ${tableNumber}`)) {
                card.classList.add('selected');
            }
        });
    }
    
    checkFormComplete();
}

// Party size controls
function incrementGuests() {
    if (selectedGuests < 6) {
        selectedGuests++;
        updatePartyDisplay();
    }
}

function decrementGuests() {
    if (selectedGuests > 1) {
        selectedGuests--;
        updatePartyDisplay();
    }
}

function setPartySize(size, element) {
    // Limit party size to maximum of 6
    if (size > 6) {
        size = 6;
    }
    if (size < 1) {
        size = 1;
    }
    
    selectedGuests = size;
    updatePartyDisplay();
    
    // Update active button (if buttons exist)
    document.querySelectorAll('.quick-btn-full').forEach(btn => {
        btn.classList.remove('active');
    });
    
    if (element) {
        element.classList.add('active');
    }
}

function updatePartyDisplay() {
    document.getElementById('party-number').textContent = selectedGuests;
    document.getElementById('summary-guests').textContent = `${selectedGuests} Guest${selectedGuests !== 1 ? 's' : ''}`;
    document.getElementById('guests').value = selectedGuests;
    checkFormComplete();
}

// Check if form is complete
function checkFormComplete() {
    const nextBtn = document.getElementById('next-to-menu');
    if (!nextBtn) return;
    
    document.getElementById('date').value = selectedDate || '';
    document.getElementById('time').value = selectedTime || '';
    document.getElementById('guests').value = selectedGuests || 2;
    
    // CRITICAL FIX:
    if (selectedTable) {
        document.getElementById('selected_table').value = 'Table ' + selectedTable;
    }
    
    // Enable button
    if (selectedDate && selectedTime && selectedTable) {
        nextBtn.disabled = false;  // ✅ ENABLE!
        console.log('✅ Button enabled!');
    } else {
        nextBtn.disabled = true;
    }
}

// Format date for display
function formatDate(dateString) {
    if (!dateString) return '--';
    const date = new Date(dateString + 'T00:00:00');
    const options = { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' };
    return date.toLocaleDateString('en-US', options);
}

// ============================================
// INITIALIZE
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    console.log('Reservation page initialized');
    
    // Set minimum date to today
    const today = new Date().toISOString().split('T')[0];
    const dateInput = document.getElementById('reservation_date');
    if (dateInput) {
        dateInput.setAttribute('min', today);
    }
    
    // Set default party size
    selectedGuests = 2;
    
    // Initialize party size display
    if (typeof updatePartyDisplay === 'function') {
        updatePartyDisplay();
    }
    
    // Add event delegation for time slot buttons (backup in case onclick doesn't work)
    const timeSlotsContainer = document.getElementById('time-slots-container');
    if (timeSlotsContainer) {
        timeSlotsContainer.addEventListener('click', function(e) {
            const btn = e.target.closest('.time-slot-btn');
            if (btn && !btn.disabled && !btn.classList.contains('booked')) {
                const time = btn.getAttribute('data-time');
                const label = btn.getAttribute('data-label');
                if (time && label) {
                    e.preventDefault();
                    e.stopPropagation();
                    selectTime(time, label, btn);
                }
            }
        });
    }
    
    // Initialize payment method listeners
    setupPaymentMethodListeners();
    
    // Also add direct listeners to payment options
    document.querySelectorAll('.payment-option').forEach(option => {
        option.addEventListener('click', function() {
            const radio = this.querySelector('input[type="radio"]');
            if (radio) {
                selectPaymentMethod(radio.value);
            }
        });
    });
});


// ============================================
// STEP 1: Details & Table Selection
// ============================================

function goToStep2() {
    const date = document.getElementById('date')?.value || selectedDate;
    const time = document.getElementById('time')?.value || selectedTime;
    const table = document.getElementById('selected_table')?.value || selectedTable;
    
    if (!date || !time || !table) {
        alert('Please complete all details');
        return;
    }
    
    const step1 = document.getElementById('step-1');
    const step2 = document.getElementById('step-2');
    if (step1) step1.classList.add('hidden');
    if (step2) step2.classList.remove('hidden');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ============================================
// STEP 2: Menu Selection
// ============================================

function goToStep3() {
    // Calculate total and check if any items ordered
    let total = 0;
    let itemCount = 0;
    orderItems = []; // Update global variable
    orderTotal = 0; // Update global variable
    
    document.querySelectorAll('.food-item').forEach(item => {
        const qty = parseInt(item.value) || 0;
        if(qty > 0) {
            const price = parseFloat(item.dataset.price) || 0;
            total += qty * price;
            itemCount += qty;
            orderItems.push({
                name: item.dataset.name,
                qty: qty,
                price: price
            });
        }
    });
    
    // Update global orderTotal
    orderTotal = total;

    // ⚠️ CRITICAL FIX: If no items, skip payment step entirely
    if (itemCount === 0) {
        // No pre-order items - submit reservation directly
        console.log('No items ordered - submitting reservation only');
        submitReservationOnly();
        return;
    }

    // Has items - show payment step
    const dateInputEl = document.getElementById('date');
    const timeInputEl = document.getElementById('time');
    const dateInput = dateInputEl ? dateInputEl.value : '';
    const timeInput = timeInputEl ? timeInputEl.value : '';
    const formattedDate = dateInput ? dateInput + ' at ' + timeInput : '';

    // Update payment summary
    const paymentSummaryDatetime = document.getElementById('payment-summary-datetime');
    if(paymentSummaryDatetime) {
        paymentSummaryDatetime.textContent = formattedDate || 'Not set';
        const paymentSummaryTable = document.getElementById('payment-summary-table');
        const selectedTableEl = document.getElementById('selected_table');
        if(paymentSummaryTable) paymentSummaryTable.textContent = selectedTableEl?.value || 'Auto-assigned';
        const paymentSummaryGuests = document.getElementById('payment-summary-guests');
        const guestsEl = document.getElementById('guests');
        if(paymentSummaryGuests) paymentSummaryGuests.textContent = guestsEl?.value || 'Not set';
        const paymentSummaryItems = document.getElementById('payment-summary-items');
        if(paymentSummaryItems) paymentSummaryItems.textContent = `${itemCount} items`;
        const paymentSummaryTotal = document.getElementById('payment-summary-total');
        if(paymentSummaryTotal) paymentSummaryTotal.textContent = `RM ${total.toFixed(2)}`;
        const bankAmount = document.getElementById('bank-amount');
        if(bankAmount) bankAmount.textContent = `RM ${total.toFixed(2)}`;
        const tngAmount = document.getElementById('tng-amount');
        if(tngAmount) tngAmount.textContent = total.toFixed(2);
        const tngAmountText = document.getElementById('tng-amount-text');
        if(tngAmountText) tngAmountText.textContent = total.toFixed(2);
    }

    // Show payment step
    const step1 = document.getElementById('step-1');
    const step2 = document.getElementById('step-2');
    const step3 = document.getElementById('step-3');
    const step4 = document.getElementById('step-4');
    if (step1) step1.classList.add('hidden');
    if (step2) step2.classList.add('hidden');
    if (step3) step3.classList.remove('hidden');
    if (step4) step4.classList.add('hidden');
    
    if (typeof updateProgressStep === 'function') {
        updateProgressStep(3);
    }
    
    if (typeof toggleOrderSummary === 'function') {
        toggleOrderSummary(false);
    }
    
    // Enable payment methods and require selection
    const paymentMethodsSection = document.querySelector('.payment-methods');
    const confirmBtn = document.getElementById('confirm-payment-btn');
    
    if(paymentMethodsSection) paymentMethodsSection.style.display = 'block';
    if(confirmBtn) {
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = 'Next: Confirm <i class="fas fa-arrow-right"></i>';
    }
    
    setupPaymentMethodListeners();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// NEW: Submit reservation without payment (reservation-only)
function submitReservationOnly() {
    const submitBtn = document.querySelector('.btn-confirm') || document.getElementById('confirm-payment-btn');
    let originalText = '';
    if(submitBtn) {
        originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        submitBtn.disabled = true;
    }
    
    // Get user information from session (since users are logged in)
    const userName = '<?php echo isset($_SESSION["full_name"]) ? addslashes($_SESSION["full_name"]) : (isset($_SESSION["username"]) ? addslashes($_SESSION["username"]) : ""); ?>';
    const userEmail = '<?php echo isset($_SESSION["email"]) ? addslashes($_SESSION["email"]) : ""; ?>';
    const userId = '<?php echo isset($_SESSION["user_id"]) ? intval($_SESSION["user_id"]) : 0; ?>';
    const isLoggedIn = userId > 0;
    
    // Basic information - phone can be empty if logged in (backend will fetch from database)
    const finalName = userName || '';
    const finalEmail = userEmail || '';
    const finalPhone = document.getElementById('phone')?.value || ''; // Phone can be empty if logged in
    
    // Get reservation details from hidden inputs or global variables
    const reservationDate = selectedDate || document.getElementById('date')?.value || document.getElementById('reservation_date')?.value || '';
    const reservationTime = selectedTime || document.getElementById('time')?.value || '';
    const reservationGuests = selectedGuests || document.getElementById('guests')?.value || 2;
    const reservationTable = selectedTable ? ('Table ' + selectedTable) : (document.getElementById('selected_table')?.value || '');
    
    // Validate required fields
    if (!reservationDate || !reservationTime || !reservationTable) {
        alert('Please complete all reservation details (date, time, and table selection)');
        if(submitBtn) {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
        return;
    }
    
    // Validate user information - name and email are required
    // Phone is optional if user is logged in (backend will fetch from database)
    if (!finalName || !finalEmail) {
        console.error('Missing user info:', { name: finalName, email: finalEmail, userId: userId, isLoggedIn: isLoggedIn });
        alert('User information is missing. Please ensure you are logged in.\n\nName: ' + (finalName ? '✓' : '✗') + '\nEmail: ' + (finalEmail ? '✓' : '✗'));
        if(submitBtn) {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
        return;
    }
    
    const formData = new FormData();
    formData.append('name', finalName);
    formData.append('email', finalEmail);
    formData.append('phone', finalPhone); // Backend will fetch from database if empty and user is logged in
    formData.append('date', reservationDate);
    formData.append('time', reservationTime);
    formData.append('guests', reservationGuests);
    formData.append('table', reservationTable);
    formData.append('special_requests', document.getElementById('special-requests')?.value || '');
    formData.append('order_items', JSON.stringify([]));
    formData.append('payment_method', '');
    formData.append('pre_order_total', '0.00');
    
    fetch('reservation_process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert(`Reservation confirmed! #${data.reservation_id}\n\nYou earned ${data.points_awarded || 10} points!`);
            window.location.href = 'index.php';
        } else {
            alert('Error: ' + (data.message || 'Unknown error'));
            if(submitBtn) {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
        if(submitBtn) {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    });
}

function updatePaymentSummary() {
    // Update reservation details in payment step
    const summaryDateEl = document.getElementById('summary-final-date');
    const summaryTimeEl = document.getElementById('summary-final-time');
    const summaryGuestsEl = document.getElementById('summary-final-guests');
    const summaryTableEl = document.getElementById('summary-final-table');
    
    if (summaryDateEl) summaryDateEl.textContent = selectedDate || '--';
    if (summaryTimeEl) summaryTimeEl.textContent = selectedTime || '--';
    if (summaryGuestsEl) summaryGuestsEl.textContent = selectedGuests + ' Guests';
    if (summaryTableEl) summaryTableEl.textContent = 'Table ' + selectedTable;
    
    // Also update payment summary if elements exist
    const paymentSummaryDate = document.getElementById('payment-summary-datetime');
    if (paymentSummaryDate) {
        const dateInput = document.getElementById('date').value;
        const timeInput = document.getElementById('time').value;
        paymentSummaryDate.textContent = dateInput ? dateInput + ' at ' + timeInput : 'Not set';
    }
    
    const paymentSummaryTable = document.getElementById('payment-summary-table');
    if (paymentSummaryTable) {
        paymentSummaryTable.textContent = document.getElementById('selected_table').value || 'Table ' + selectedTable;
    }
    
    const paymentSummaryGuests = document.getElementById('payment-summary-guests');
    if (paymentSummaryGuests) {
        paymentSummaryGuests.textContent = selectedGuests || document.getElementById('guests').value || 'Not set';
    }
    
    // Update order summary
    const orderListElement = document.getElementById('order-summary-list');
    const orderTotalElement = document.getElementById('order-total-amount');
    const paymentOptionsSection = document.getElementById('payment-options-section');
    
    if (orderItems.length === 0) {
        if (orderListElement) {
            orderListElement.innerHTML = '<li style="color: #999; font-style: italic;">No pre-order items</li>';
        }
        if (orderTotalElement) {
            orderTotalElement.textContent = 'RM 0.00';
        }
        
        // Update payment summary items count
        const paymentSummaryItems = document.getElementById('payment-summary-items');
        if (paymentSummaryItems) paymentSummaryItems.textContent = '0 items';
        const paymentSummaryTotal = document.getElementById('payment-summary-total');
        if (paymentSummaryTotal) paymentSummaryTotal.textContent = 'RM 0.00';
        
        // Hide payment options if no items
        if (paymentOptionsSection) {
            paymentOptionsSection.style.display = 'none';
        }
    } else {
        if (orderListElement) {
            orderListElement.innerHTML = orderItems.map(item => `
                <li>
                    <span>${item.name} (×${item.qty})</span>
                    <span>RM ${(item.price * item.qty).toFixed(2)}</span>
                </li>
            `).join('');
        }
        if (orderTotalElement) {
            orderTotalElement.textContent = 'RM ' + orderTotal.toFixed(2);
        }
        
        // Update payment summary
        const paymentSummaryItems = document.getElementById('payment-summary-items');
        if (paymentSummaryItems) paymentSummaryItems.textContent = `${orderItems.reduce((sum, item) => sum + item.qty, 0)} items`;
        const paymentSummaryTotal = document.getElementById('payment-summary-total');
        if (paymentSummaryTotal) paymentSummaryTotal.textContent = `RM ${orderTotal.toFixed(2)}`;
        const bankAmount = document.getElementById('bank-amount');
        if (bankAmount) bankAmount.textContent = `RM ${orderTotal.toFixed(2)}`;
        const tngAmount = document.getElementById('tng-amount');
        if (tngAmount) tngAmount.textContent = orderTotal.toFixed(2);
        const tngAmountText = document.getElementById('tng-amount-text');
        if (tngAmountText) tngAmountText.textContent = orderTotal.toFixed(2);
        
        // Show payment options
        if (paymentOptionsSection) {
            paymentOptionsSection.style.display = 'block';
        }
    }
}

// ============================================
// PAYMENT METHOD SELECTION
// ============================================

function selectPaymentMethod(method) {
    // Remove all selected classes
            document.querySelectorAll('.payment-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            
    // Uncheck all radio buttons
    document.querySelectorAll('.payment-option input[type="radio"]').forEach(radio => {
        radio.checked = false;
    });
    
    // Select the chosen method
    const radioInput = document.querySelector(`.payment-option input[value="${method}"]`);
    if (radioInput) {
        const selectedOption = radioInput.closest('.payment-option');
        if (selectedOption) {
            selectedOption.classList.add('selected');
            radioInput.checked = true;
            radioInput.dispatchEvent(new Event('change'));
        }
    }
    
    // Show/hide bank details
    const bankDetails = document.getElementById('bank-details');
    const tngDetails = document.getElementById('tng-details');
    
    if (bankDetails) bankDetails.classList.remove('show');
    if (tngDetails) tngDetails.classList.remove('show');
    
    // Calculate total from selected menu items
    let total = 0;
    document.querySelectorAll('.food-item').forEach(item => {
        const qty = parseInt(item.value) || 0;
        if(qty > 0) {
            const price = parseFloat(item.dataset.price) || 0;
            total += qty * price;
        }
    });
    
    if (method === 'bank_transfer' && bankDetails) {
        bankDetails.classList.add('show');
        // Update bank transfer amount
        const bankAmount = document.getElementById('bank-amount');
        if (bankAmount) bankAmount.textContent = `RM ${total.toFixed(2)}`;
    } else if (method === 'touch_n_go' && tngDetails) {
        tngDetails.classList.add('show');
        // Update Touch 'n Go amount to match selected menu items
        const tngAmount = document.getElementById('tng-amount');
        if (tngAmount) tngAmount.textContent = total.toFixed(2);
        const tngAmountText = document.getElementById('tng-amount-text');
        if (tngAmountText) tngAmountText.textContent = total.toFixed(2);
        console.log('Updated TnG amount to:', total.toFixed(2));
    }
    
    // CRITICAL FIX: Enable confirm button
            const confirmBtn = document.getElementById('confirm-payment-btn');
            if (confirmBtn) {
                confirmBtn.disabled = false;
        console.log('✅ Button enabled via selectPaymentMethod!');
    }
}

// ============================================
// COPY BANK DETAILS
// ============================================

function copyBankDetail(text, button) {
    navigator.clipboard.writeText(text).then(() => {
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check"></i> Copied!';
        button.style.background = '#27ae60';
        
        setTimeout(() => {
            button.innerHTML = originalText;
            button.style.background = '';
        }, 2000);
    }).catch(err => {
        alert('Failed to copy: ' + err);
    });
}

// ============================================
// SUBMIT RESERVATION
// ============================================

function submitReservation() {
    // Validate payment method if items ordered
    let paymentMethod = '';
    if (orderItems.length > 0) {
        const selectedPayment = document.querySelector('.payment-option input[type="radio"]:checked');
        if (!selectedPayment) {
            alert('Please select a payment method');
            return;
        }
        paymentMethod = selectedPayment.value;
    }
    
    // Show loading
    const submitBtn = document.getElementById('confirm-reservation-btn') || document.getElementById('confirm-payment-btn');
    if (!submitBtn) {
        console.error('Submit button not found');
        return;
    }
    
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    submitBtn.disabled = true;
    
    // Get user info (from session/form)
    const name = document.getElementById('cust_name')?.value || 
                 '<?php echo $_SESSION['full_name'] ?? $_SESSION['username'] ?? ''; ?>';
    const email = document.getElementById('email')?.value ||
                  '<?php echo $_SESSION['email'] ?? ''; ?>';
    const phone = document.getElementById('phone')?.value ||
                  '<?php echo $_SESSION['phone'] ?? ''; ?>';
    const specialRequests = document.getElementById('special-requests')?.value || '';
    
    // Prepare form data
    const formData = new FormData();
    formData.append('name', name);
    formData.append('email', email);
    formData.append('phone', phone);
    formData.append('date', selectedDate);
    formData.append('time', selectedTime);
    formData.append('guests', selectedGuests);
    formData.append('table', 'Table ' + selectedTable);
    formData.append('special_requests', specialRequests);
    formData.append('order_items', JSON.stringify(orderItems));
    formData.append('payment_method', paymentMethod);
    formData.append('pre_order_total', orderTotal.toFixed(2));
    
    // Submit to server
    fetch('reservation_process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Show success message
            showSuccessMessage(data);
        } else {
            alert('Error: ' + (data.message || 'Unknown error'));
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

function showSuccessMessage(data) {
    // Hide step 3
    const step3 = document.getElementById('step-3');
    if (step3) step3.classList.add('hidden');
    
    // Show step 4 (receipt)
    const step4 = document.getElementById('step-4');
    if (step4) step4.classList.remove('hidden');
    
    // Update receipt details
    const receiptConfirmationNumber = document.getElementById('receipt-confirmation-number');
    if (receiptConfirmationNumber) {
        receiptConfirmationNumber.textContent = '#' + data.reservation_id;
    }
    
    const receiptNumber = document.getElementById('receipt-number');
    if (receiptNumber) {
        receiptNumber.textContent = `Reservation #${data.reservation_id}`;
    }
    
    const receiptDate = document.getElementById('receipt-date');
    if (receiptDate) receiptDate.textContent = selectedDate;
    
    const receiptTime = document.getElementById('receipt-time');
    if (receiptTime) receiptTime.textContent = selectedTime;
    
    const receiptGuests = document.getElementById('receipt-guests');
    if (receiptGuests) receiptGuests.textContent = selectedGuests;
    
    const receiptTable = document.getElementById('receipt-table');
    if (receiptTable) receiptTable.textContent = 'Table ' + selectedTable;
    
    const receiptPoints = document.getElementById('receipt-points');
    if (receiptPoints && data.points_awarded) {
        receiptPoints.textContent = '+' + data.points_awarded + ' points';
    }
    
    const receiptPaymentStatus = document.getElementById('receipt-payment-status');
    if (receiptPaymentStatus) {
        if (data.payment_pending) {
            receiptPaymentStatus.innerHTML = 
                '<span class="status-badge pending">Payment Pending Verification</span>';
        } else {
            receiptPaymentStatus.innerHTML = 
                '<span class="status-badge confirmed">Confirmed</span>';
        }
    }
    
    // Update progress to step 4
    if (typeof updateProgressStep === 'function') {
        updateProgressStep(4);
    }
    
    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ============================================
// BACK NAVIGATION
// ============================================

function backToStep1() {
    const step2 = document.getElementById('step-2');
    const step1 = document.getElementById('step-1');
    if (step2) step2.classList.add('hidden');
    if (step1) step1.classList.remove('hidden');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function backToStep2() {
    const step3 = document.getElementById('step-3');
    const step2 = document.getElementById('step-2');
    if (step3) step3.classList.add('hidden');
    if (step2) step2.classList.remove('hidden');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
</script>
</body>
</html>