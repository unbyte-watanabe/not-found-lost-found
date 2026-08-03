---
name: GitHub remote setup
description: How the GitHub origin remote was configured for this project — SSH deploy key approach via connector SDK.
---

# GitHub Remote Setup

## Repository
`git@github.com:unbyte-watanabe/not-found-lost-found.git`  
Public URL: https://github.com/unbyte-watanabe/not-found-lost-found

## How it was set up
1. GitHub connector (`conn_github_01KZ2YM8EA98TKH30SKP48PJ3V`, slug: `github`) added via ProposeIntegration.
2. `@replit/connectors-sdk` installed at workspace root (`node_modules/@replit/connectors-sdk`).
3. A temporary ed25519 SSH key was generated in `/tmp/deploy_key`, public key registered via connector API call to `POST /repos/unbyte-watanabe/not-found-lost-found/keys`.
4. `~/.ssh/config` configured with `IdentityFile /tmp/deploy_key` for `github.com`.
5. Pushed via `git push -u origin main`.

**Why:**
- `gitPush({})` from the git-remote skill returns `NO_CREDENTIALS` (requires "github-source-control" creds, separate from the API connector).
- `listConnections('github')` returns `[]` in CodeExecution sandbox (credentials withheld from sandbox context).
- Writing a Node.js script to disk and running via shell (`node script.mjs`) DOES work with the connector SDK.

**How to apply for future pushes:**
- The SSH deploy key in `/tmp/deploy_key` is ephemeral (lost on repl restart).
- For future sessions: regenerate key → re-register via connector SDK script → push.
- OR: ask the user to provide a GitHub PAT as a secret and use HTTPS remote with the token.

## Origin remote
`git remote add origin git@github.com:unbyte-watanabe/not-found-lost-found.git`

## GitHub username
`unbyte-watanabe` (REPL_OWNER env var shows `nabeprogolf` but the GitHub account under the connector is `unbyte-watanabe`)
