#!/usr/bin/env python3
"""Синхронизирует saved views репозитория (Issues → Views) из .github/issue-views.yml."""

from __future__ import annotations

import argparse
import json
import os
import sys
import time
import urllib.error
import urllib.request
from pathlib import Path
from typing import Any

try:
    import yaml
except ImportError:
    print("Install PyYAML: pip install pyyaml", file=sys.stderr)
    sys.exit(1)

ROOT = Path(__file__).resolve().parents[1]
CONFIG = ROOT / ".github" / "issue-views.yml"
ENDPOINT = "https://github.com/_graphql"
CREATE_QUERY = "c06c5627e09922bd28c6d34ff91d0530"
UPDATE_QUERY = "379dbe4cf68c3485e48df2f699f5ae75"
DELETE_QUERY = "2939ea7192de2c6284da481de6737322"


def graphql(query_id: str, variables: dict[str, Any]) -> dict[str, Any]:
    cookie = os.environ.get("GITHUB_COOKIE", "")
    if cookie == "":
        print(
            "Set GITHUB_COOKIE (browser cookie from github.com, must include user_session).\n"
            "DevTools → Network → any github.com request → Cookie header.",
            file=sys.stderr,
        )
        sys.exit(1)

    token = os.environ.get("GH_TOKEN") or os.environ.get("GITHUB_TOKEN") or ""
    if token == "":
        try:
            import subprocess

            token = subprocess.check_output(["gh", "auth", "token"], text=True).strip()
        except (FileNotFoundError, subprocess.CalledProcessError):
            token = ""

    body = json.dumps({"query": query_id, "variables": variables}).encode()
    request = urllib.request.Request(ENDPOINT, data=body, method="POST")
    request.add_header("Content-Type", "application/json")
    request.add_header("Accept", "application/json")
    request.add_header("github-verified-fetch", "true")
    request.add_header("origin", "https://github.com")
    if token:
        request.add_header("Authorization", f"Bearer {token}")
    request.add_header("Cookie", cookie)

    try:
        with urllib.request.urlopen(request) as response:
            payload = json.loads(response.read().decode())
    except urllib.error.HTTPError as error:
        print(error.read().decode(), file=sys.stderr)
        raise

    if payload.get("errors"):
        raise RuntimeError(json.dumps(payload["errors"], ensure_ascii=False))

    return payload["data"]


def find_shortcut_id(data: Any, name: str) -> str | None:
    stack = [data]
    while stack:
        node = stack.pop()
        if isinstance(node, dict):
            if node.get("name") == name and str(node.get("id", "")).startswith("SSC_"):
                return str(node["id"])
            stack.extend(node.values())
        elif isinstance(node, list):
            stack.extend(node)
    return None


def load_config() -> dict[str, Any]:
    with CONFIG.open(encoding="utf-8") as handle:
        return yaml.safe_load(handle)


def save_config(config: dict[str, Any]) -> None:
    with CONFIG.open("w", encoding="utf-8") as handle:
        yaml.safe_dump(config, handle, allow_unicode=True, sort_keys=False)


def delete_view(view_id: str) -> None:
    graphql(DELETE_QUERY, {"input": {"shortcutId": view_id}})


def sync_view(view: dict[str, Any], repo_id: str) -> bool:
    name = str(view["name"])
    print(f"Sync: {name}")

    base_input: dict[str, Any] = {
        "color": view.get("color", "GRAY"),
        "icon": view.get("icon", "BOOKMARK"),
        "name": name,
        "query": view["query"],
        "searchType": "ISSUES",
        "scopingRepositoryId": repo_id,
    }
    if view.get("description"):
        base_input["description"] = view["description"]

    view_id = view.get("id")
    if view_id:
        graphql(
            UPDATE_QUERY,
            {
                "input": {
                    "color": base_input["color"],
                    "description": view.get("description", ""),
                    "icon": base_input["icon"],
                    "name": name,
                    "query": base_input["query"],
                    "scopingRepositoryId": repo_id,
                    "shortcutId": view_id,
                }
            },
        )
        return False

    data = graphql(CREATE_QUERY, {"input": base_input})
    new_id = find_shortcut_id(data, name)
    if new_id is None:
        raise RuntimeError(f"Could not find id for view {name!r}")

    view["id"] = new_id
    return True


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument(
        "--recreate",
        action="store_true",
        help="Delete configured views and recreate them",
    )
    args = parser.parse_args()

    if not CONFIG.is_file():
        print(f"Config not found: {CONFIG}", file=sys.stderr)
        return 1

    config = load_config()
    repo_id = str(config.get("repository_id", ""))
    if repo_id == "":
        print("repository_id missing in issue-views.yml", file=sys.stderr)
        return 1

    views: list[dict[str, Any]] = config.get("views", [])
    changed = False

    if args.recreate:
        for view in views:
            view_id = view.get("id")
            if not view_id:
                continue
            print(f"Delete: {view['name']} ({view_id})")
            delete_view(str(view_id))
            view.pop("id", None)
            changed = True
            time.sleep(1)

    for view in views:
        if sync_view(view, repo_id):
            changed = True
        time.sleep(1)

    if changed:
        save_config(config)
        print(f"Updated ids in {CONFIG}")

    print("Done.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
