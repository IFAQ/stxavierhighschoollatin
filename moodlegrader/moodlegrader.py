import csv
from datetime import datetime

months = [
	'', # here to make indices line up with month number
	'January',
	'February',
	'March',
	'April',
	'May',
	'June',
	'July',
	'August',
	'September',
	'October',
	'November',
	'December'
]

passing_score = 8

file_to_read = "quizresults.csv"
file_to_write = "graderesults.csv"

current_student_first_name = None
current_student_last_name = None

header_row = None

students = {}

with open(file_to_read, 'rU') as read_handle:
	csv_reader = csv.reader(read_handle, delimiter=",")
	line_count = 0

	for row in csv_reader:
		# deal with header row
		if line_count == 0:
			print("Last Name", "First Name", "Date", "Passing Scores", "Daily Points")
			line_count += 1
			continue
		
		# deal with empty rows (e.g., end)
		if row[6] == "" or row[9] == "-":
			line_count += 1
			continue
		
		# individual records
		student_key = row[1] + ":" + row[0]
		
		if not(student_key in students):
			new_student = {
				"first_name": row[1],
				"last_name": row[0],
				"quiz_results_by_date": {}
			}
			
			students[student_key] = new_student
		
		current_student = students[student_key]
		
		completion_date_components = [
			str(months.index(row[4].split(" ")[1])),
			str(row[4].split(" ")[0]),
			str(row[4].split(" ")[2])[2:]
		]
		
		for i in range(len(completion_date_components)):
			completion_date_components[i] = completion_date_components[i].zfill(2)
		
		completion_date_reformatted = "/".join(completion_date_components)
				
		completion_date = datetime.strptime(completion_date_reformatted, "%m/%d/%y")
		completion_date_key = completion_date.strftime("%m/%d/%Y")
		
		if not(completion_date_key in current_student["quiz_results_by_date"]):
			current_student["quiz_results_by_date"][completion_date_key] = [0, 0, []]
		
		current_student["quiz_results_by_date"][completion_date_key][0] += 1
		current_student["quiz_results_by_date"][completion_date_key][2].append(row[8])
		
		if int(float(row[7])) >= passing_score:
			current_student["quiz_results_by_date"][completion_date_key][1] += 1
					
		line_count += 1

with open(file_to_write, mode="w") as write_handle:
	result_writer = csv.writer(write_handle, delimiter=',', quotechar='"', quoting=csv.QUOTE_MINIMAL)
	
	result_writer.writerow([
		"Last Name",
		"First Name",
		"Date",
		"Attempts",
		"Quality Scores",
		"Total Time",
		"Average Time"
	])
	
	for student_key in students:
		student = students[student_key]
		
		for date in student["quiz_results_by_date"]:
			attempt_counter = student["quiz_results_by_date"][date][0]
			pass_counter = student["quiz_results_by_date"][date][1]
			
			total_seconds = 0
			
			for duration in student["quiz_results_by_date"][date][2]:
				duration_components = duration.split(" ")
				if "min" in duration:
					total_seconds += 60 * int(float(duration_components[0]))
					
					if "secs" in duration:
						total_seconds += int(float(duration_components[2]))
				else:
					total_seconds += int(float(duration_components[0]))
			
			total_minutes = int(total_seconds / 60)
			remainder_seconds = total_seconds % 60
			
			average_duration = total_seconds / len(student["quiz_results_by_date"][date][2])
			
			average_minutes = int(average_duration / 60)
			average_seconds = average_duration % 60
			
			result_writer.writerow([
				student["last_name"],
				student["first_name"],
				date,
				attempt_counter,
				pass_counter,
				str(total_minutes) + ":" + str(remainder_seconds).zfill(2),
				str(average_minutes) + ":" + str(average_seconds).zfill(2)
			])
			
			print(student["last_name"], student["first_name"], date, attempt_counter, pass_counter, str(total_minutes) + ":" + str(remainder_seconds).zfill(2), str(average_minutes) + ":" + str(average_seconds).zfill(2))
