#!/usr/bin/env python3
"""Extract one Wavefront OBJ object group (o NAME) into a standalone OBJ file."""
from __future__ import annotations

import argparse
from pathlib import Path


def split_object(src: Path, object_name: str, dest: Path) -> dict:
    verts: list[str] = []
    vns: list[str] = []
    vts: list[str] = []
    faces: list[str] = []
    mtls: list[str] = []
    in_target = False
    global_v = 0
    global_vt = 0
    global_vn = 0
    v_off = vt_off = vn_off = 0

    with src.open("r", encoding="utf-8", errors="replace") as fh:
        for line in fh:
            if line.startswith("o "):
                if in_target:
                    break
                in_target = line[2:].strip() == object_name
                if in_target:
                    v_off = global_v
                    vt_off = global_vt
                    vn_off = global_vn
                continue
            if line.startswith("v "):
                global_v += 1
                if in_target:
                    verts.append(line)
                continue
            if line.startswith("vt "):
                global_vt += 1
                if in_target:
                    vts.append(line)
                continue
            if line.startswith("vn "):
                global_vn += 1
                if in_target:
                    vns.append(line)
                continue
            if not in_target:
                continue
            if line.startswith("f "):
                faces.append(line)
            elif line.startswith("usemtl ") or line.startswith("mtllib "):
                mtls.append(line)

    if not verts:
        raise SystemExit(f"Object not found or empty: {object_name}")

    def remap_face(face_line: str) -> str:
        parts = face_line.strip().split()[1:]
        remapped = []
        for token in parts:
            chunks = token.split("/")
            if chunks[0]:
                idx = int(chunks[0]) - v_off
                chunks[0] = str(idx)
            if len(chunks) > 1 and chunks[1]:
                idx = int(chunks[1]) - vt_off
                chunks[1] = str(idx)
            if len(chunks) > 2 and chunks[2]:
                idx = int(chunks[2]) - vn_off
                chunks[2] = str(idx)
            remapped.append("/".join(chunks))
        return "f " + " ".join(remapped) + "\n"

    dest.parent.mkdir(parents=True, exist_ok=True)
    with dest.open("w", encoding="utf-8") as out:
        out.write(f"# split from {src.name}\n")
        for line in mtls:
            out.write(line)
        out.write(f"o {object_name}\n")
        out.writelines(verts)
        out.writelines(vts)
        out.writelines(vns)
        for face in faces:
            out.write(remap_face(face))

    return {
        "object": object_name,
        "verts": len(verts),
        "faces": len(faces),
        "path": str(dest),
    }


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("src", type=Path)
    parser.add_argument("object_name")
    parser.add_argument("dest", type=Path)
    args = parser.parse_args()
    info = split_object(args.src, args.object_name, args.dest)
    print(info)


if __name__ == "__main__":
    main()