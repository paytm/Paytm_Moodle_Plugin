Introduction

This is the readme file for Paytm Payment Gateway Plugin Integration for Moodle v3.x. 
The provided Package helps store merchants to redirect customers to the Paytm Payment Gateway when they choose PAYTM as their payment method. 
After the customer has finished the transaction they are redirected back to an appropriate page on the merchant site depending on the status of the transaction.
The aim of this document is to explain the procedure of installation and configuration of the Package on the merchant website.


Installation

- Unzip "paytm.zip".
- In moodle root folder navigate to "moodle > enrol" and paste the unzipped folder "paytm".
- From the backend of your moodle site (administration), go to your module list (select "Site administration" -> Plugins -> Enrolments -> Paytm).
- Locate the module "Paytm". 


Configuration

- Click on "Paytm" to configure the settings.
- You should choose the Paytm Environment (either to Sandbox or Production)
- Enter paytm Merchant Key, Merchant ID, website in the listed parameters on configuration tab. These parameters are Mandatory.
- Click on save.

# Paytm PG URL Details
	staging	
		Transaction URL             => https://securegw-stage.paytm.in/theia/processTransaction
		Transaction Status Url      => https://securegw-stage.paytm.in/merchant-status/getTxnStatus

	Production
		Transaction URL             => https://securegw.paytm.in/theia/processTransaction
		Transaction Status Url      => https://securegw.paytm.in/merchant-status/getTxnStatus
