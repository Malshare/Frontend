A project-level philosophy and design rules document that captures:
 
1. Core Philosophy 
- The system must run for years with minimal human intervention
- Performance is a feature, not an optimization
 
2. Design Rules (derived from philosophy + existing patterns)
Minimize Dependencies
- The fewer external dependencies, the fewer things that break, deprecate, or need updating
- Current state: one dependency (AWS SDK) — keep it that way unless absolutely necessary 
- Prefer PHP standard library over packages
- CDN-hosted frontend libs (Bootstrap, jQuery) — no build step, no node_modules
 
No Frameworks
- Plain PHP is the framework. It doesn't have breaking version upgrades that force rewrites
- No Laravel, Symfony, or similar — they impose maintenance burden and upgrade cycles
- The current architecture (includes, a ServerObject class, template files) is intentional, not primitive
 
Stable, Proven Technology
- MySQL, Apache, PHP, jQuery — battle-tested, well-documented, not going anywhere
- Avoid trendy tech that may not exist in 5 years
- S3-compatible storage (Wasabi) — commodity API, vendor-switchable
 
Security is Non-Negotiable 
- Prepared statements for all SQL (already enforced) 
- Input sanitization at every boundary 
- reCAPTCHA on sensitive operations
- API key auth with rate limiting
- This is a malware repository — security isn't optional, it's existential 
 
Performance by Simplicity
- No ORM overhead — direct SQL is faster and more predictable
- Server-side rendering — no SPA hydration cost, no API-then-render round trips
- Minimal JavaScript — enhance, don't replace, server-rendered HTML
- Keep pages lightweight — users on security networks may have constrained bandwidth 
 
Deployment Must Be Trivial 
- Docker Compose up and it works 
- Environment variables for all config — no config files to manage 
- No build steps, no transpilation, no asset pipelines 
 
3. Conventions 
- File naming: lowercase, underscores (e.g., server_includes.php)
- Template pattern: header.php → nav.php → content → footer.php
- Business logic lives in ServerObject class in server_includes.php
- Database constants for table names (e.g., TBL_SAMPLES) 
- All user input passes through secure() before use
 
4. What NOT to Do
- Don't add npm/node/webpack/vite — there is no JavaScript build step and never will be
- Don't introduce an ORM — raw SQL with prepared statements is the standard
- Don't refactor into a framework — the current structure is intentional 
- Don't add unnecessary abstractions — three lines of clear code beats a clever helper 
- Don't chase modern PHP patterns (traits, enums, attributes) unless they genuinely simplify 
 
Implementation 
1. Create worktree from master (Sacred Practice #2)
2. Write AGENT.md at project root with the content above, refined into clean prose 
3. Verify file reads well and is scannable 
4. Commit via Guardian agent 
 
Verification 
- Read the file and confirm it's concise, scannable, and actionable
- Ensure it doesn't contradict existing patterns in the codebase 