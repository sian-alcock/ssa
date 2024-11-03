# Questions component

We were tasked with replicating current functionality. It comprised of questions with multiple options. Each option when chosen could either:
- Show another question
- Show a popup with more information and have a continue button within the popup
- Show a success message
- Show an unsuccessful message

Each question can also have:
-	A none of the above option

## Challenge
Adding the above functionality inside Gravity Forms would have been very time consuming if possible. The editor experience would have been a real challenge also.

## Solution
Separate this logic into a reusable component that can send information to Gravity form if needed. We created a `question` post type that contains all the logic necessary. 

The question component within the page builder chooses the first question by linking to that post. The question itself will determine what happens when options are chosen. 

## Connection to Gravity forms
When added to a page an option is there to enable sending the question answers to a form page. If a success message is shown to the user. The button presented will submit the chosen options to the page specified as a query string.

The query string values that are sent are taken from:
- Key - `question` key
- value - `option` value

## Loading new questions
When an option is chosen that is defined to navigate to another question. We use `wp-ajax` to load in the new question from the question id stored on the option (`questions.php`). 

## Gravity forms Salesforce Integration
The integration is handled by https://www.crmperks.com/plugins/gravity-forms-plugins/gravity-forms-salesforce-plugin/

There is a simple `feed` that uses a salesforce account for credentials to map fields from the form to fields on salesforce.

### Share your idea form
The form this was introduced for and the only form at the point of writing that is mapped to Salesforce is the share your idea form. 

The share your idea form is mapped to a `lead` within salesforce. The `RecordTypeId` is manually mapped to a static custom value for the specific ID to ensure that in Salesforce the lead is mapped to the correct record type.