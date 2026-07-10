-- Royal Beverages Seed Data
-- Default Categories
INSERT INTO categories (name, slug, description, image_url) VALUES
('Whiskey', 'whiskey', 'Premium aged spirits from around the world.', 'whiskey.webp'),
('Vodka', 'vodka', 'Pure, distilled spirits for cocktails and neat drinking.', 'vodka.webp'),
('Rum', 'rum', 'Tropical and dark aged rums.', 'rum.webp'),
('Wine', 'wine', 'Fine red, white and sparkling wines.', 'wine.webp'),
('Gin', 'gin', 'Botanical-infused spirits.', 'gin.webp')
ON CONFLICT (slug) DO NOTHING;

-- Default Suppliers
INSERT INTO suppliers (name, email) VALUES
('Global Spirits Distributing', 'orders@globalspirits.com'),
('Fine Wines Import Co.', 'contact@finewines.com')
ON CONFLICT (name) DO NOTHING;

-- Default Warehouses
INSERT INTO warehouses (name, address) VALUES
('Colombo Central', '123 Galle Road, Colombo 03'),
('Kandy Distribution', '45 Main Street, Kandy')
ON CONFLICT (name) DO NOTHING;

-- Default Products
INSERT INTO products (name, slug, description, price_cents, image_url, category_id, supplier_id) VALUES
('Johnnie Walker Black Label', 'johnnie-walker-black', 'Iconic blended scotch whiskey.', 850000, 'johnnie-walker-black.webp', 1, 1),
('Absolut Vodka', 'absolut-vodka', 'Pure Swedish vodka.', 450000, 'vodka.webp', 2, 1),
('Bacardi Superior White Rum', 'bacardi-rum', 'Classic mixing rum.', 380000, 'rum.webp', 3, 1),
('Casillero del Diablo Cabernet', 'casillero-cabernet', 'Rich Chilean red wine.', 320000, 'wine.webp', 4, 2),
('Glenfiddich 12 Year', 'glenfiddich-12', 'Single malt scotch whisky.', 950000, 'products_1.webp', 1, 1),
('Macallan 18 Year', 'macallan-18', 'Premium aged scotch whisky.', 2500000, 'products_2.webp', 1, 1),
('Jameson Irish Whiskey', 'jameson-irish', 'Classic smooth Irish whiskey.', 650000, 'products_3.webp', 1, 1),
('Bulleit Bourbon', 'bulleit-bourbon', 'Kentucky straight bourbon.', 720000, 'whiskey_ed_1.webp', 1, 1),
('Woodford Reserve', 'woodford-reserve', 'Premium Kentucky bourbon.', 880000, 'whiskey_ed_2.webp', 1, 1),
('Maker''s Mark', 'makers-mark', 'Handmade Kentucky bourbon whisky.', 750000, 'products_1.webp', 1, 1),
('Talisker 10 Year', 'talisker-10', 'Peated single malt scotch.', 1100000, 'products_2.webp', 1, 1),
('Lagavulin 16 Year', 'lagavulin-16', 'Rich, peaty single malt scotch.', 1800000, 'products_3.webp', 1, 1),
('Chivas Regal 12', 'chivas-regal-12', 'Blended scotch whisky.', 780000, 'whiskey_ed_1.webp', 1, 1),
('Monkey Shoulder', 'monkey-shoulder', 'Blended malt scotch whisky.', 820000, 'whiskey_ed_2.webp', 1, 1),
('Laphroaig 10', 'laphroaig-10', 'Islay single malt scotch.', 1250000, 'products_1.webp', 1, 1),
('Ardbeg 10', 'ardbeg-10', 'Intense Islay single malt.', 1300000, 'products_2.webp', 1, 1),
('Buffalo Trace', 'buffalo-trace', 'Kentucky straight bourbon.', 680000, 'products_3.webp', 1, 1),
('Elijah Craig', 'elijah-craig', 'Small batch bourbon.', 740000, 'whiskey_ed_1.webp', 1, 1),
('Knob Creek', 'knob-creek', 'Kentucky straight bourbon.', 890000, 'whiskey_ed_2.webp', 1, 1),
('Four Roses Single Barrel', 'four-roses', 'Premium single barrel bourbon.', 920000, 'products_1.webp', 1, 1)
ON CONFLICT (slug) DO UPDATE SET image_url = EXCLUDED.image_url;

-- Initial Stock Levels
INSERT INTO stock (product_id, warehouse_id, quantity, reserved) VALUES
(1, 1, 100, 0),
(2, 1, 150, 0),
(3, 1, 200, 0),
(4, 2, 50, 0),
(5, 1, 80, 0),
(6, 1, 10, 0),
(7, 1, 120, 0),
(8, 1, 60, 0),
(9, 1, 40, 0),
(10, 1, 90, 0),
(11, 1, 30, 0),
(12, 1, 15, 0),
(13, 1, 110, 0),
(14, 1, 85, 0),
(15, 1, 45, 0),
(16, 1, 25, 0),
(17, 1, 75, 0),
(18, 1, 65, 0),
(19, 1, 55, 0),
(20, 1, 35, 0)
ON CONFLICT (product_id, warehouse_id) DO NOTHING;

-- Default Admin User (Password: password)
-- Hash generated with: password_hash('password', PASSWORD_BCRYPT)
INSERT INTO users (name, email, password_hash, is_admin) VALUES
('System Admin', 'admin@royal-liquor.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE)
ON CONFLICT (email) DO NOTHING;

-- Regular Users (Password: password)
INSERT INTO users (name, email, password_hash, is_admin) VALUES
('Jane Doe', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', FALSE),
('John Smith', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', FALSE)
ON CONFLICT (email) DO NOTHING;

-- Flavor Profiles
INSERT INTO flavor_profiles (product_id, sweetness, bitterness, strength, smokiness, fruitiness, spiciness, tags) VALUES
(1, 2, 4, 8, 7, 2, 5, '{"smoky", "intense", "classic"}'),
(2, 1, 2, 8, 0, 1, 3, '{"smooth", "clean", "pure"}'),
(3, 7, 1, 7, 1, 8, 4, '{"sweet", "tropical", "rich"}'),
(4, 3, 6, 6, 2, 7, 2, '{"fruity", "dry", "elegant"}'),
(5, 2, 3, 8, 4, 4, 4, '{"smooth", "classic", "fruity"}'),
(6, 1, 2, 9, 3, 6, 5, '{"sherry", "rich", "premium"}'),
(7, 4, 2, 7, 1, 5, 4, '{"smooth", "light", "sweet"}'),
(8, 3, 4, 9, 3, 3, 7, '{"spicy", "bold", "rye"}'),
(9, 3, 3, 8, 2, 4, 6, '{"smooth", "rich", "caramel"}'),
(10, 4, 3, 8, 2, 3, 5, '{"sweet", "smooth", "classic"}'),
(11, 2, 4, 8, 8, 2, 6, '{"peaty", "smoky", "maritime"}'),
(12, 1, 5, 9, 9, 3, 5, '{"intense", "peaty", "smoky"}'),
(13, 3, 3, 7, 3, 5, 4, '{"smooth", "balanced", "fruity"}'),
(14, 4, 2, 7, 2, 6, 4, '{"sweet", "fruity", "smooth"}'),
(15, 1, 5, 8, 9, 2, 5, '{"peaty", "smoky", "medicinal"}'),
(16, 1, 4, 9, 9, 3, 6, '{"intense", "peaty", "smoky"}'),
(17, 3, 3, 8, 2, 4, 5, '{"smooth", "caramel", "classic"}'),
(18, 3, 4, 8, 3, 4, 6, '{"bold", "spicy", "balanced"}'),
(19, 2, 4, 9, 4, 3, 7, '{"bold", "spicy", "woody"}'),
(20, 3, 4, 9, 3, 5, 6, '{"rich", "fruity", "complex"}')
ON CONFLICT (product_id) DO NOTHING;

-- Feedback (Reviews)
INSERT INTO feedback (user_id, product_id, rating, comment, is_verified_purchase) VALUES
(2, 1, 5, 'Absolutely fantastic. Classic for a reason.', TRUE),
(3, 1, 4, 'Very good, solid flavor profile.', FALSE),
(2, 2, 5, 'Clean and perfect for cocktails.', TRUE),
(3, 4, 5, 'One of the best red wines I have tasted recently.', TRUE);

-- Cocktail Recipes
INSERT INTO cocktail_recipes (name, description, instructions, image_url, difficulty, preparation_time, serves) VALUES
('Whiskey Sour', 'A classic balance of sweet and sour.', '1. Mix whiskey, lemon juice, and simple syrup.\n2. Shake with ice.\n3. Strain into a glass.', 'old_fashioned.png', 'easy', 5, 1),
('Classic Mojito', 'Refreshing rum cocktail with mint and lime.', '1. Muddle mint leaves with sugar and lime.\n2. Add rum and top with club soda.\n3. Garnish with a mint sprig.', 'daiquiri.png', 'medium', 10, 1);

-- Recipe Ingredients
-- Linking Whiskey Sour (ID 1) to Johnnie Walker (ID 1)
-- Linking Classic Mojito (ID 2) to Bacardi Rum (ID 3)
INSERT INTO recipe_ingredients (recipe_id, product_id, quantity, unit, is_optional) VALUES
(1, 1, 2.00, 'oz', FALSE),
(2, 3, 2.00, 'oz', FALSE)
ON CONFLICT (recipe_id, product_id) DO NOTHING;
