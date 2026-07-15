-- Per-visit GST toggle
-- Adds an optional apply_gst flag to each visit so staff can include/exclude GST
-- for an individual visit, overriding the clinic-wide GST setting.
--   NULL / absent → follow the clinic GST setting (inv_gst_enabled)
--   1             → force GST on for this visit
--   0             → force GST off for this visit
-- The app detects this column at runtime, so it keeps working whether or not
-- this migration has been applied. Run once on the server database.

ALTER TABLE `progress_report`
    ADD COLUMN `apply_gst` TINYINT(1) NULL DEFAULT NULL AFTER `payment_status`;
