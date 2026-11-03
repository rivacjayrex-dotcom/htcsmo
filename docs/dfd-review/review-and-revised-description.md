# Figure 3. Level 1 Data Flow Diagram

The corrected diagram retains the eight processes and three logical data stores in the supplied design. Final validation is assigned to the **SMO In-Charge**, as confirmed by the project owner. Repeated entities and stores in the drawing represent the same roles and repositories, rather than separate databases.

## Review of the supplied diagram

| Area | Finding in the original | Correction |
| --- | --- | --- |
| External entities | The drawing says Employee and School Admin, while the narrative also refers to organizations, faculty, staff, and signatories. | Use Requestor for people submitting requests and Authorized Signatory / School Admin for approval roles. Identify SMO explicitly. Grouped labels show roles sharing the displayed interface. An organization should be represented through its authorized requestor if organizations are within scope. |
| 2.0 Authentication | Login flows cover Employee and School Admin but omit SMO. | Include all system users and check the stored credentials, role, and account-access status. A registration result does not imply immediate authorization if account activation is required. |
| 4.0 Approval | A request list goes to School Admin, but there is no approval decision flowing back from the reviewer. The arrow labelled Request Approval Stage comes from D2. | Add the reviewer's approval/rejection/clarification decision and remarks. Read the request and approval history from D2; write the decision and current approval stage back to D2. |
| 5.0 Validation | Validation receives human parameters but has no request-data input from D2. Its owner also conflicts with the project workflow. | Read fully approved requests from D2; receive the SMO's validation decision; write validation status and remarks to D2; return the validation result to the requestor. |
| 6.0 Supply management | D2 supplies validated requests, but fulfillment status is not written back to D2. Processing Update points from SMO to the process although the text describes a confirmation sent to SMO. | Name the SMO input Fulfillment details and stock adjustments. Return Processing confirmation to SMO. Update both the request status in D2 and inventory in D3. Return fulfillment/release information to the requestor. |
| 7.0 Tracking | Request status is returned to the employee, but no user lookup input is shown. The narrative incorrectly says tracking updates the request status. | Add a request ID / lookup input. Read and display the current status; approval, validation, and fulfillment processes change the stored status. |
| 8.0 Notifications | Only deadline information and a deadline alert are shown, although the narrative also promises approval notifications. | Read request status, approval status, and deadline data from D2 and recipient account/contact data from D1. Generate the applicable approval/status notifications and deadline alerts. |
| Terminology | Labels such as Validation Status Updated and Submission Data Request Record mix events, actions, and data. | Use noun phrases such as Validation status and remarks and New supply request record. Use verb phrases for process names. |
| Layout | Long lines wrap around the entire page and create difficult crossings. | Repeat entities and logical stores with consistent names and IDs to keep each flow legible. Process numbers identify functions rather than an execution sequence. |

This review does **not** claim that the original has a direct external-entity-to-store flow: its principal issues are missing inputs/outputs and inconsistent meanings, rather than that specific connection error.

## Revised description for the manuscript

**Figure 3. Level 1 Data Flow Diagram**

The Level 1 Data Flow Diagram in Figure 3 illustrates the movement of data among the system's external entities, eight processes, and three logical data stores: Accounts (D1), Supply Requests (D2), and Inventory (D3). The external roles include requestors, authorized signatories or school administrators, and the SMO In-Charge. Repeated symbols represent the same entities and data stores and are used to improve readability.

In Register Account (1.0), a requestor provides personal information and registration details. The system checks existing account information, stores the new account record in Accounts (D1), and returns the registration result. In Authenticate Account (2.0), requestors, signatories, and the SMO In-Charge submit their login credentials. The process retrieves the corresponding credentials, role, and account status from D1 and returns an authentication result. Access depends on successful authentication and the user's account permissions.

In Submit Supply Request (3.0), a requestor or authorized administrator submits the required supply request details. The system stores the request record in Supply Requests (D2) and returns a submission confirmation. Approve Request (4.0) retrieves request details and approval history from D2 and presents pending requests to the appropriate authorized signatory or school administrator. The reviewer submits an approval decision and any remarks, after which the process records the decision and current approval stage in D2. This interaction is repeated for each required approval stage; rejection and clarification decisions are also recorded.

Validate Request (5.0) retrieves fully approved requests from D2 and presents them to the SMO In-Charge for verification. The SMO provides a validation decision and any remarks. The process stores the validation result in D2 and provides the requestor with the validation details. Manage Supply (6.0) retrieves requests eligible for fulfillment from D2 and current inventory information from Inventory (D3). Based on the fulfillment details entered by the SMO, it records processing and release information in D2, applies the appropriate stock changes in D3, and returns processing confirmation to the SMO and fulfillment updates to the requestor.

Track Request Status (7.0) receives a request identifier or other lookup criteria from the requestor, retrieves the corresponding status and progress information from D2, and displays the result. This process reads the latest recorded status; the approval, validation, and supply-management processes maintain the status records.

Send Notification (8.0) uses approval status, request status, and deadline information from D2 together with recipient account and contact information from D1 to produce the applicable notifications. The SMO is notified of requests requiring attention, including approved requests and requests approaching or exceeding the processing deadline. Depending on the event and access permissions, notifications are also directed to the requestor and assigned signatory. The current project implements a three-working-day service-level policy, including a day-two warning and a day-three overdue check. These timing rules are part of the notification logic and are not represented as control-flow arrows.

## Scope and verification notes

- This is a correction of the supplied eight-process scope, not an exhaustive model of every feature in the application. Separate account administration, reporting, archiving, and notification-history management would require additional flows or decomposition if included in the manuscript's scope.
- Final validation by SMO was confirmed by the project owner during this review.
- The code and README implement in-app and email notifications. The neutral name Send Notification avoids promising browser/device push delivery that has not been established by the supplied evidence.
- Three **working** days is the wording used by the current deadline service. The exact calculation, working-day calendar, and warning boundary should be documented in the system's business rules. A deadline is a target, not a guarantee of fulfillment.
- A DFD shows data movement and transformation. It does not establish execution order, real-time delivery guarantees, or every permission rule by itself.
- The parent/context DFD was not supplied. Balancing of the external inputs and outputs against that parent diagram remains to be checked. Include the same external roles and corresponding data flows at both levels, allowing grouped flows to be decomposed consistently.
- The editable `.drawio` file opens in diagrams.net. The SVG is scalable; the PNG is suitable for ordinary document insertion; the PDF preserves vector shapes for printing.

## References

- [IBM: What is a data flow diagram?](https://www.ibm.com/think/topics/data-flow-diagram) — notation, process/store naming, and Level 1 decomposition.
- [Visual Paradigm: DFD connection rules](https://www.visual-paradigm.com/support/documents/vpuserguide/1284/992/36119_dataflowdiag.html) — permitted connections between processes, entities, and stores.
- [Project README](../../README.md) — approval workflow, SMO verification, notification channels, and deadline alerts.
- [DeadlineNotificationService.php](../../app/Services/DeadlineNotificationService.php) — deadline-recipient selection and three-working-day notification wording.

The diagram's topology was checked for process inputs and outputs and for flows involving a process. All text was checked against the drawing bounds, and the exported figure was visually inspected.
