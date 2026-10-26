DELIMITER //
-- Make the audit tables append-only. Rows can be added, never removed or rewritten.
DROP TRIGGER IF EXISTS activity_logs_no_delete//
DROP TRIGGER IF EXISTS activity_logs_no_update//
DROP TRIGGER IF EXISTS intrusion_alerts_no_delete//
DROP TRIGGER IF EXISTS intrusion_alerts_protect//

CREATE TRIGGER activity_logs_no_delete BEFORE DELETE ON activity_logs
FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'activity_logs is append-only'//

CREATE TRIGGER activity_logs_no_update BEFORE UPDATE ON activity_logs
FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'activity_logs is append-only'//

CREATE TRIGGER intrusion_alerts_no_delete BEFORE DELETE ON intrusion_alerts
FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'intrusion_alerts is append-only'//

-- Status fields (e.g. acknowledged) may change; the evidence itself may not.
CREATE TRIGGER intrusion_alerts_protect BEFORE UPDATE ON intrusion_alerts
FOR EACH ROW
BEGIN
  IF NOT (OLD.user_id <=> NEW.user_id) OR NOT (OLD.threat_type <=> NEW.threat_type)
     OR NOT (OLD.severity <=> NEW.severity) OR NOT (OLD.description <=> NEW.description)
     OR NOT (OLD.response_action <=> NEW.response_action) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'intrusion_alerts evidence cannot be changed';
  END IF;
END//
DELIMITER ;
