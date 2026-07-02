#!/usr/bin/env python3
def read_chapters(doc_path):
    from docx import Document
    doc = Document(doc_path)
    chapters = []
    for p in doc.paragraphs:
        if p.text.strip().lower().startswith('chapter'):
            chapters.append(p.text.strip())
    return chapters

if __name__=='__main__':
    print('chapter_reader')
