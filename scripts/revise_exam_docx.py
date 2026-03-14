#!/usr/bin/env python3
import copy
import re
import sys
import zipfile
from datetime import datetime, timezone
from xml.etree import ElementTree as ET


W_NS = "http://schemas.openxmlformats.org/wordprocessingml/2006/main"
XML_NS = "http://www.w3.org/XML/1998/namespace"
NS = {"w": W_NS}

ET.register_namespace("w", W_NS)
ET.register_namespace("mc", "http://schemas.openxmlformats.org/markup-compatibility/2006")
ET.register_namespace("o", "urn:schemas-microsoft-com:office:office")
ET.register_namespace("r", "http://schemas.openxmlformats.org/officeDocument/2006/relationships")
ET.register_namespace("m", "http://schemas.openxmlformats.org/officeDocument/2006/math")
ET.register_namespace("v", "urn:schemas-microsoft-com:vml")
ET.register_namespace("w10", "urn:schemas-microsoft-com:office:word")
ET.register_namespace("w14", "http://schemas.microsoft.com/office/word/2010/wordml")
ET.register_namespace("w15", "http://schemas.microsoft.com/office/word/2012/wordml")
ET.register_namespace("w16cex", "http://schemas.microsoft.com/office/word/2018/wordml/cex")
ET.register_namespace("w16cid", "http://schemas.microsoft.com/office/word/2016/wordml/cid")
ET.register_namespace("w16", "http://schemas.microsoft.com/office/word/2018/wordml")
ET.register_namespace("w16sdtdh", "http://schemas.microsoft.com/office/word/2020/wordml/sdtdatahash")
ET.register_namespace("w16se", "http://schemas.microsoft.com/office/word/2015/wordml/symex")
ET.register_namespace("sl", "http://schemas.openxmlformats.org/schemaLibrary/2006/main")


REVISIONS = [
    "What is an Enterprise Architecture model?",
    "Describe the two main components of Enterprise Systems Architecture.",
    "Using examples from ERP implementations in organizations, describe four major reasons for adopting ERP systems.",
    "Using examples from ERP implementations in organizations, differentiate between the Purchasing module and the Production module.",
    "ERP systems are often deployed through cloud hosting (Software as a Service, SaaS) rather than being installed on an organization's local infrastructure. State three advantages of cloud hosting. State three challenges of cloud hosting.",
    "Using examples, describe at least four benefits of using Artificial Intelligence systems in organizations.",
    "Describe any four negative effects of using Artificial Intelligence (AI) systems in organizations.",
    "Describe any two challenges that organizations face when adopting AI.",
    "CompTech, a Tanzania-based business involved in the design, manufacture, and sale of high-demand products such as footwear, sports shoes, and equipment since 2006, adopted an ERP system in 2014 and completed implementation in 2015 across several modules, including payroll, human resources, and budgeting, using a well-known ERP vendor. Although the ERP system was initially considered successful, from July 2016 onward the firm experienced rising customer attrition and operating costs, while sales volume declined. Despite the significant capital invested in ERP implementation and maintenance, the firm is now concerned that the project may have failed. Assume the firm has engaged you as an ERP Management Consultant and asked you to prepare and present a generic report. Identify the most probable factors behind the ERP failure. Recommend measures that the firm should take to address the problem.",
    "Explain the three levels of enterprise systems integration and interoperability in Enterprise Architecture.",
    "Which of the three levels of enterprise systems integration and interoperability in Enterprise Architecture is the most difficult to achieve? Give a reason for your answer.",
    "Suppose an airline company wants its online reservation system to provide immediate service to premier members, expedited service to frequent-flyer members, and regular service to all other customers. In relation to implementing this reservation system using the REST architectural style: what is REST? What are the advantages? What are the disadvantages?",
    "Suppose an organization plans to move all its digital assets to a cloud service. Outline four security threats associated with hosting digital assets in the cloud. Describe four approaches for securing digital assets hosted in the cloud.",
    "Define the term business process.",
    "Business process modelling is an essential step in designing and implementing an ERP system. Outline four advantages of process models. Illustrate the standardized set of symbols used in flowchart-based process models. Name any four common flowchart structures. Differentiate between flowcharts and Event-driven Process Chains (EPCs).",
    "Using a flow diagram, explain the five phases involved in the ERP implementation life cycle.",
    "Using suitable examples, explain six areas in which hidden costs may arise and lead to budget overruns during ERP implementation.",
    "Outline any three reasons for adopting ERP systems.",
    "Name and briefly describe any two enterprise architecture frameworks.",
    "Explain why change management is a key determinant of the success or failure of ERP implementation in organizations.",
    "A patient visited a hospital, was attended by a physician, and returned home with a prescription. Unfortunately, some medicines were unavailable, so the patient received an incomplete dose. The patient later returned to collect the remaining medicines and went directly to the pharmacist. The pharmacist checked the prescription status. If refills remained, the pharmacist checked inventory for the refill or an alternative. If no refills remained, the patient was referred to a physician. The physician then checked the patient records to determine whether a refill was allowed. If a refill was allowed, the physician referred the patient back to the pharmacist; otherwise, the physician evaluated an alternative treatment option. Back at the pharmacy, inventory was checked again. If the medicine was in stock, the pharmacist refilled the prescription. If the requested medicine was out of stock, the patient received an out-of-stock notification and was advised to contact a physician. After the refill, the patient collected the medicines and returned home. List all the processes involved. Draw a swimlane diagram to represent the prescription-refill business function.",
    "List any four steps involved in the system maintenance phase of an ERP system.",
    "Considering the Academic Register Information System (ARIS3) of the University of Dar es Salaam as a case study of ERP implementation in universities, describe any four processes that have been improved by this system.",
    "Describe the drawbacks of client-centric enterprise systems architectures.",
    "You have recently joined a leading Dar es Salaam-based automobile firm as a Technology Consultant. The firm has manufactured various models of passenger cars and three-wheelers since 2007 and now plans to streamline its main business process, namely manufacturing, through ERP adoption. Prepare and present a manufacturing process-cum-data model for the firm to serve as a blueprint for streamlining operations. Your process-cum-data model should include: a representative list of core processes, with a brief description of each, for an ideal automobile manufacturing company; and a representative list of entities, with a brief description of each, to form a suitable data model for a manufacturing company. Justify your report with charts that present valid information on the processes and relevant data.",
]


def qn(tag: str) -> str:
    return f"{{{W_NS}}}{tag}"


def get_first_run_properties(paragraph):
    run = paragraph.find("w:r", NS)
    if run is None:
        return None
    rpr = run.find("w:rPr", NS)
    return copy.deepcopy(rpr) if rpr is not None else None


def clear_paragraph_content(paragraph):
    for child in list(paragraph):
        if child.tag != qn("pPr"):
            paragraph.remove(child)


def flatten_cell_text(cell):
    texts = []
    for paragraph in cell.findall("w:p", NS):
        paragraph_text = "".join(text.text or "" for text in paragraph.findall(".//w:t", NS)).strip()
        if paragraph_text:
            texts.append(paragraph_text)
    return " ".join(texts)


def normalize_cell_structure(cell):
    paragraphs = cell.findall("w:p", NS)
    if paragraphs:
        keeper = paragraphs[0]
    else:
        keeper = ET.SubElement(cell, qn("p"))

    for child in list(cell):
        if child.tag == qn("tcPr") or child is keeper:
            continue
        cell.remove(child)
    return keeper


def build_deleted_run(text: str, rpr):
    deletion = ET.Element(qn("del"))
    run = ET.SubElement(deletion, qn("r"))
    if rpr is not None:
        run.append(copy.deepcopy(rpr))
    del_text = ET.SubElement(run, qn("delText"))
    del_text.text = text
    return deletion


def build_inserted_run(text: str, rpr):
    insertion = ET.Element(qn("ins"))
    run = ET.SubElement(insertion, qn("r"))
    if rpr is not None:
        run.append(copy.deepcopy(rpr))
    t = ET.SubElement(run, qn("t"))
    t.text = text
    return insertion


def set_revision_metadata(node, rev_id: int, author: str, iso_date: str):
    node.set(qn("id"), str(rev_id))
    node.set(qn("author"), author)
    node.set(qn("date"), iso_date)


def enable_track_revisions(settings_root):
    if settings_root.find("w:trackRevisions", NS) is not None:
        return
    track = ET.Element(qn("trackRevisions"))
    proof_state = settings_root.find("w:proofState", NS)
    insert_at = list(settings_root).index(proof_state) + 1 if proof_state is not None else 0
    settings_root.insert(insert_at, track)


def preserve_root_start_tag(original_xml: bytes, new_xml: bytes, root_tag: str) -> bytes:
    pattern = rf"(<{root_tag}\b[^>]*>)"
    original_match = re.search(pattern, original_xml.decode("utf-8"), flags=re.DOTALL)
    new_match = re.search(pattern, new_xml.decode("utf-8"), flags=re.DOTALL)
    if not original_match or not new_match:
        return new_xml
    return new_xml.decode("utf-8").replace(new_match.group(1), original_match.group(1), 1).encode("utf-8")


def main():
    if len(sys.argv) != 3:
        raise SystemExit("Usage: revise_exam_docx.py <input.docx> <output.docx>")

    input_path, output_path = sys.argv[1], sys.argv[2]
    author = "Codex"
    iso_date = datetime.now(timezone.utc).replace(microsecond=0).isoformat()

    with zipfile.ZipFile(input_path, "r") as zin:
        original_document_xml = zin.read("word/document.xml")
        original_settings_xml = zin.read("word/settings.xml")
        document_root = ET.fromstring(original_document_xml)
        settings_root = ET.fromstring(original_settings_xml)
        body = document_root.find("w:body", NS)
        revision_index = 1
        revision_texts = iter(REVISIONS)

        for table in body.findall("w:tbl", NS):
            for row in table.findall("w:tr", NS):
                cells = row.findall("w:tc", NS)
                if len(cells) < 2:
                    continue
                try:
                    new_text = next(revision_texts)
                except StopIteration:
                    break
                target_cell = cells[1]
                old_text = flatten_cell_text(target_cell)
                paragraph = normalize_cell_structure(target_cell)
                rpr = get_first_run_properties(paragraph)
                clear_paragraph_content(paragraph)
                deleted = build_deleted_run(old_text, rpr)
                inserted = build_inserted_run(new_text, rpr)
                set_revision_metadata(deleted, revision_index, author, iso_date)
                revision_index += 1
                set_revision_metadata(inserted, revision_index, author, iso_date)
                revision_index += 1
                paragraph.append(deleted)
                paragraph.append(inserted)

        try:
            next(revision_texts)
            raise SystemExit("Not all revisions were applied.")
        except StopIteration:
            pass

        enable_track_revisions(settings_root)

        with zipfile.ZipFile(output_path, "w") as zout:
            for info in zin.infolist():
                data = zin.read(info.filename)
                if info.filename == "word/document.xml":
                    data = ET.tostring(document_root, encoding="utf-8", xml_declaration=True)
                    data = preserve_root_start_tag(original_document_xml, data, "w:document")
                elif info.filename == "word/settings.xml":
                    data = ET.tostring(settings_root, encoding="utf-8", xml_declaration=True)
                    data = preserve_root_start_tag(original_settings_xml, data, "w:settings")
                zout.writestr(info, data)


if __name__ == "__main__":
    main()
