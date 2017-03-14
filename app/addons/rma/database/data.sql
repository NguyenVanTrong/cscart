REPLACE INTO ?:rma_properties (property_id, position, status, type, update_totals_and_inventory) VALUES ('11', '10', 'A', 'R', 'N');
REPLACE INTO ?:rma_properties (property_id, position, status, type, update_totals_and_inventory) VALUES ('12', '20', 'A', 'R', 'N');
REPLACE INTO ?:rma_properties (property_id, position, status, type, update_totals_and_inventory) VALUES ('3', '30', 'A', 'R', 'N');
REPLACE INTO ?:rma_properties (property_id, position, status, type, update_totals_and_inventory) VALUES ('4', '40', 'A', 'R', 'N');
REPLACE INTO ?:rma_properties (property_id, position, status, type, update_totals_and_inventory) VALUES ('5', '50', 'A', 'R', 'N');
REPLACE INTO ?:rma_properties (property_id, position, status, type, update_totals_and_inventory) VALUES ('6', '60', 'A', 'R', 'N');
REPLACE INTO ?:rma_properties (property_id, position, status, type, update_totals_and_inventory) VALUES ('7', '70', 'A', 'R', 'N');
REPLACE INTO ?:rma_properties (property_id, position, status, type, update_totals_and_inventory) VALUES ('8', '80', 'A', 'R', 'N');
REPLACE INTO ?:rma_properties (property_id, position, status, type, update_totals_and_inventory) VALUES ('1', '10', 'A', 'A', 'N');
REPLACE INTO ?:rma_properties (property_id, position, status, type, update_totals_and_inventory) VALUES ('2', '20', 'A', 'A', 'Y');
REPLACE INTO ?:rma_properties (property_id, position, status, type, update_totals_and_inventory) VALUES ('13', '90', 'A', 'R', 'N');

REPLACE INTO ?:status_data (status_id, param, value)
SELECT
    status_id,
    'inventory' as param,
    'I' as value
FROM ?:statuses
WHERE status = 'A' AND type = 'R';

REPLACE INTO ?:status_data (status_id, param, value)
SELECT
    status_id,
    'inventory' as param,
    'I' as value
FROM ?:statuses
WHERE status = 'C' AND type = 'R';

REPLACE INTO ?:status_data (status_id, param, value)
SELECT
    status_id,
    'inventory' as param,
    'D' as value
FROM ?:statuses
WHERE status = 'D' AND type = 'R';

REPLACE INTO ?:status_data (status_id, param, value)
SELECT
    status_id,
    'inventory' as param,
    'I' as value
FROM ?:statuses
WHERE status = 'R' AND type = 'R';