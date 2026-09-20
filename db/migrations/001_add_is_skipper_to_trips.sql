-- Migration: Add is_skipper flag to trips table
ALTER TABLE trips ADD COLUMN is_skipper BOOLEAN DEFAULT 1;
CREATE INDEX IF NOT EXISTS idx_trips_is_skipper ON trips(is_skipper);
