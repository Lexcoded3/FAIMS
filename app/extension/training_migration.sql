-- Run this once in your agriconnect database before using the training pages
-- Adds created_by to training_courses so extension workers own their courses

ALTER TABLE training_courses
    ADD COLUMN created_by INT(11) DEFAULT NULL AFTER thumbnail,
    ADD CONSTRAINT fk_course_author FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;
