---
type: "query"
date: "2026-10-29T08:05:24.895574+00:00"
question: "Why does UCreds learning platform connect so many communities?"
contributor: "graphify"
outcome: "useful"
source_nodes: ["UCreds learning platform", "MD-06 Learning platform", "LearningPlatformGateway adapter interface (Manual CSV)", "A-04 UCreds pilot platform, CSV fallback"]
---

# Q: Why does UCreds learning platform connect so many communities?

## Answer

Expanded via vocab: [ucreds, learning, platform]. UCreds learning platform (SRS/src/01-front.html, 32 links) is the one external system every document set names: SRS (M7 learning platform connection, A-04 pilot with CSV fallback, CSV exchange figure), SDD v1.0-v1.2 (system context, adapter pattern, MD-06 Learning platform, LearningPlatformGateway manual CSV), PRD (out of scope: no content hosting), UI/UX (UCreds-style offering pages, FL-19 journey) and the UCreds portal audit (Moodle stack, identity federation, 60-course directory). It bridges because MCMS keeps learning in UCreds and only exchanges CSV through an adapter. No path exists from this node to the app code (UcredsController, Template, ImportValidator, Acr, IntegrationHub): the document graph and the code graph are separate components.

## Outcome

- Signal: useful

## Source Nodes

- UCreds learning platform
- MD-06 Learning platform
- LearningPlatformGateway adapter interface (Manual CSV)
- A-04 UCreds pilot platform, CSV fallback