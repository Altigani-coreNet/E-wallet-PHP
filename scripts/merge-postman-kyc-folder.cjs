const fs = require('fs');

const collectionPath = 'Customer_Auth_API_Postman_Collection.json';
const kycPath = 'postman_kyc_folder.json';

const collection = JSON.parse(fs.readFileSync(collectionPath, 'utf8'));
const kycFolder = JSON.parse(fs.readFileSync(kycPath, 'utf8'));

const idx = collection.item.findIndex((i) => i.name === 'Banners');
if (idx === -1) {
    throw new Error('Banners folder not found');
}

const existing = collection.item.findIndex((i) => i.name === 'KYC');
if (existing !== -1) {
    collection.item.splice(existing, 1);
}

collection.item.splice(idx, 0, kycFolder);

const descAdd =
    '\n\n## KYC folder\nUse the **KYC** folder for admin reject/approve, change requests, event logs, and customer resubmit flows. Variables: `customer_id`, `change_request_id`, `customer_national_id`. See `CUSTOMER_REJECTION_WORKFLOW_PRD.md`.';

if (!collection.info.description.includes('## KYC folder')) {
    collection.info.description += descAdd;
}

const authFolder = collection.item.find((i) => i.name === 'Auth');
if (authFolder) {
    for (const reqName of ['Register', 'Login']) {
        const req = authFolder.item.find((r) => r.name === reqName);
        if (!req?.event) {
            continue;
        }
        const test = req.event.find((e) => e.listen === 'test');
        if (!test) {
            continue;
        }
        const joined = test.script.exec.join('\n');
        if (!joined.includes('customer_id')) {
            test.script.exec.push(
                "    if (json.data?.customer?.id) pm.collectionVariables.set('customer_id', json.data.customer.id);",
            );
        }
    }
}

fs.writeFileSync(collectionPath, JSON.stringify(collection, null, 4));
console.log('Inserted KYC folder at index', idx);
