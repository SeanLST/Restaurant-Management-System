-- Add Beverage Items to Menu
-- Run this in phpMyAdmin if you already have menu items and just want to add beverages

USE thewellingtondb;

INSERT IGNORE INTO menu (name, description, price, category) VALUES 
('Peppermint Tea', 'Refreshing mint herbal infusion, naturally caffeine-free', 8.00, 'Beverages'),
('Green Tea', 'Premium Japanese sencha green tea', 9.00, 'Beverages'),
('Americano', 'Espresso with hot water, smooth and bold', 9.00, 'Beverages'),
('Espresso', 'Rich, full-bodied Italian coffee shot', 7.00, 'Beverages'),
('Watermelon Juice', 'Refreshing and hydrating watermelon juice', 13.00, 'Beverages'),
('Green Detox Juice', 'Spinach, celery, cucumber, apple, and lemon', 16.00, 'Beverages'),
('Berry Blast Juice', 'Mixed berries with apple and honey', 16.00, 'Beverages');

